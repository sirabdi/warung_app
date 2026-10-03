<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\StockIn;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\WeighedProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages are served by Inertia, every change goes through the JSON API that
 * TanStack Query talks to.
 */
class CoreFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function login(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_guests_get_401_from_the_api(): void
    {
        $this->postJson('/api/sales', ['items' => []])->assertUnauthorized();
    }

    public function test_add_and_edit_a_product(): void
    {
        $this->login();
        $sembako = Category::factory()->create(['name' => 'Sembako']);
        $snack = Category::factory()->create(['name' => 'Snack']);

        $this->postJson('/api/products', ['name' => 'Indomie', 'category_id' => $sembako->id, 'sell_price' => 3500, 'stock' => 10])
            ->assertCreated()
            ->assertJson(['message' => 'Indomie ditambahkan.']);

        $product = Product::firstWhere('name', 'Indomie');
        $this->assertSame(3500, $product->sell_price);
        $this->assertSame(10, $product->stock);
        $this->assertSame($sembako->id, $product->category_id);

        $this->putJson("/api/products/{$product->id}", [
            'name' => 'Indomie Goreng', 'category_id' => $snack->id, 'sell_price' => 4000, 'cost_price' => 2800,
        ])->assertOk();

        $product->refresh();
        $this->assertSame('Indomie Goreng', $product->name);
        $this->assertSame($snack->id, $product->category_id);
        $this->assertSame(4000, $product->sell_price);
        $this->assertSame(10, $product->stock, 'stock must not change from the product form');
    }

    public function test_product_names_must_be_unique(): void
    {
        $this->login();
        Product::factory()->create(['name' => 'Aqua']);

        $this->postJson('/api/products', ['name' => 'Aqua', 'category_id' => Category::factory()->create()->id, 'sell_price' => 4000])
            ->assertJsonValidationErrors('name');
    }

    public function test_a_product_needs_an_existing_category(): void
    {
        $this->login();

        $this->postJson('/api/products', ['name' => 'Aqua', 'sell_price' => 4000])
            ->assertJsonValidationErrors(['category_id' => 'Kategori wajib dipilih.']);

        $this->postJson('/api/products', ['name' => 'Aqua', 'category_id' => 999, 'sell_price' => 4000])
            ->assertJsonValidationErrors(['category_id' => 'Kategori #999 tidak ditemukan.']);
    }

    public function test_category_crud(): void
    {
        $this->login();

        $this->postJson('/api/categories', ['name' => 'Minuman'])->assertCreated();
        $this->postJson('/api/categories', ['name' => 'Minuman'])
            ->assertJsonValidationErrors(['name' => 'Kategori dengan nama Minuman sudah ada.']);

        $category = Category::firstWhere('name', 'Minuman');
        $this->putJson("/api/categories/{$category->id}", ['name' => 'Minuman dingin'])->assertOk();
        $this->assertSame('Minuman dingin', $category->fresh()->name);

        Product::factory()->count(2)->create(['category_id' => $category->id]);
        $this->getJson('/api/categories')
            ->assertJsonPath('data.0.name', 'Minuman dingin')
            ->assertJsonPath('data.0.products_count', 2)
            ->assertJsonPath('meta.total', 1);
        $this->getJson('/api/categories/options')->assertJsonPath('categories.0.name', 'Minuman dingin');

        $this->get('/categories')
            ->assertInertia(fn ($page) => $page->component('Categories')->has('categories.data', 1));
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $this->login();
        $used = Category::factory()->create(['name' => 'Rokok']);
        Product::factory()->create(['category_id' => $used->id]);
        $empty = Category::factory()->create();

        $this->deleteJson("/api/categories/{$used->id}")
            ->assertJsonValidationErrors(['category' => 'Kategori Rokok masih dipakai 1 produk. Pindahkan produknya dulu.']);
        $this->assertModelExists($used);

        $this->deleteJson("/api/categories/{$empty->id}")->assertOk();
        $this->assertModelMissing($empty);
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $this->login();
        $drinks = Category::factory()->create(['name' => 'Minuman']);
        Product::factory()->create(['name' => 'Aqua', 'category_id' => $drinks->id]);
        Product::factory()->create(['name' => 'Beras']);

        $this->getJson("/api/products?category={$drinks->id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Aqua')
            ->assertJsonPath('data.0.category_name', 'Minuman');
    }

    public function test_seeders_create_default_categories_and_sort_existing_products(): void
    {
        Product::factory()->create(['name' => 'Rokok Sampoerna', 'category_id' => null]);
        Product::factory()->create(['name' => 'Aqua Botol 600ml', 'category_id' => null]);
        $chosen = Category::factory()->create(['name' => 'Pilihan pemilik']);
        Product::factory()->create(['name' => 'Beras 1kg', 'category_id' => $chosen->id]);

        $this->seed(ProductCategorySeeder::class);
        $this->seed(ProductCategorySeeder::class); // safe to run twice

        $this->assertSame(count(CategorySeeder::DEFAULTS) + 1, Category::count());
        $this->assertSame('Rokok', Product::firstWhere('name', 'Rokok Sampoerna')->category->name);
        $this->assertSame('Minuman', Product::firstWhere('name', 'Aqua Botol 600ml')->category->name);
        $this->assertSame('Pilihan pemilik', Product::firstWhere('name', 'Beras 1kg')->category->name, 'an existing choice is kept');
    }

    public function test_stock_in_increases_stock(): void
    {
        $this->login();
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/stock-in', ['product_id' => $product->id, 'qty' => 12])
            ->assertCreated()
            ->assertJson(['current_stock' => 17]);

        $this->assertSame(17, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_ins', ['product_id' => $product->id, 'qty' => 12]);
    }

    public function test_a_sale_reduces_stock_and_is_recorded(): void
    {
        $this->login();
        $first = Product::factory()->create(['stock' => 10, 'cost_price' => 2800, 'sell_price' => 3500]);
        $second = Product::factory()->create(['stock' => 4, 'sell_price' => 4000]);

        $this->postJson('/api/sales', ['items' => [
            ['product_id' => $first->id, 'qty' => 2],
            ['product_id' => $second->id, 'qty' => 1],
        ]])->assertCreated()->assertJson(['total' => 11000, 'item_count' => 3]);

        $this->assertSame(8, $first->fresh()->stock);
        $this->assertSame(3, $second->fresh()->stock);
        $this->assertSame(11000, (int) Transaction::sum('total'));
        $this->assertCount(1, Transaction::distinct()->pluck('code'));
        $this->assertSame(2800, Transaction::where('product_id', $first->id)->value('cost_price'));
    }

    public function test_a_sale_is_rejected_when_stock_is_short(): void
    {
        $this->login();
        $product = Product::factory()->create(['name' => 'Beras', 'stock' => 1]);

        $this->postJson('/api/sales', ['items' => [['product_id' => $product->id, 'qty' => 3]]])
            ->assertJsonValidationErrors(['items' => 'Stok Beras tinggal 1.']);

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, Transaction::count());
    }

    public function test_old_prices_are_kept_when_a_product_price_changes(): void
    {
        $this->login();
        $product = Product::factory()->create(['stock' => 10, 'sell_price' => 3000]);

        $this->postJson('/api/sales', ['items' => [['product_id' => $product->id, 'qty' => 1]]]);
        $this->putJson("/api/products/{$product->id}", ['name' => $product->name, 'sell_price' => 5000]);

        $this->assertSame(3000, (int) Transaction::sum('total'));
    }

    public function test_cashier_api_lists_products(): void
    {
        $this->login();
        $drinks = Category::factory()->create(['name' => 'Minuman']);
        Product::factory()->create(['name' => 'Kopi', 'category_id' => $drinks->id, 'sell_price' => 2000, 'stock' => 9]);

        $this->getJson('/api/cashier/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Kopi')
            ->assertJsonPath('data.0.category_id', $drinks->id)
            ->assertJsonPath('data.0.category_name', 'Minuman')
            ->assertJsonPath('data.0.sell_price', 2000)
            ->assertJsonPath('data.0.stock', 9)
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_daily_report(): void
    {
        $this->login();
        $bestSeller = Product::factory()->create(['name' => 'Kopi', 'stock' => 50, 'cost_price' => 1200, 'sell_price' => 2000]);
        Product::factory()->create(['name' => 'Gula', 'stock' => 2]);

        $this->postJson('/api/sales', ['items' => [['product_id' => $bestSeller->id, 'qty' => 5]]]);

        // Yesterday's sale must not be counted.
        Transaction::create([
            'code' => 'YESTERDAY', 'product_id' => $bestSeller->id, 'qty' => 1, 'price' => 2000,
            'cost_price' => 1200, 'total' => 2000, 'sold_at' => now()->subDay(),
        ]);

        // The page ships the first payload…
        $this->get('/report')
            ->assertInertia(fn ($page) => $page
                ->component('Report')
                ->where('summary.revenue', 10000)
                ->where('bestSellers.0.name', 'Kopi')
                ->where('lowStock.data.0.name', 'Gula')
            );

        // …and the API serves every later refetch, including other days.
        $this->getJson('/api/report')
            ->assertOk()
            ->assertJson([
                'summary' => ['revenue' => 10000, 'profit' => 4000, 'items' => 5, 'sales' => 1],
                'lowStockThreshold' => 5,
            ])
            ->assertJsonPath('bestSellers.0.qty', 5);

        $this->getJson('/api/report?date='.now()->subDay()->toDateString())
            ->assertJsonPath('summary.revenue', 2000)
            ->assertJsonPath('isToday', false);
    }

    public function test_stock_in_history_is_listed(): void
    {
        $this->login();
        $product = Product::factory()->create();
        StockIn::create(['product_id' => $product->id, 'qty' => 6, 'date' => today()]);

        $this->get('/stock-in')
            ->assertInertia(fn ($page) => $page->component('StockIn')
                ->where('history.data.0.qty', 6)
                ->where('history.meta.total', 1));

        $this->getJson('/api/stock-in/history')->assertJsonPath('data.0.qty', 6);
    }

    public function test_stock_in_history_is_paginated_newest_first(): void
    {
        $this->login();
        $product = Product::factory()->create();
        foreach (range(1, 12) as $qty) {
            StockIn::create(['product_id' => $product->id, 'qty' => $qty, 'date' => today()]);
        }

        $this->getJson('/api/stock-in/history?page=2&per_page=5')
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.qty', 7)
            ->assertJsonPath('meta', ['page' => 2, 'per_page' => 5, 'total' => 12, 'last_page' => 3]);
    }

    public function test_products_are_paginated_on_the_server(): void
    {
        $this->login();
        foreach (range(1, 12) as $i) {
            Product::factory()->create(['name' => sprintf('Produk %02d', $i)]);
        }

        $this->getJson('/api/products?page=2&per_page=5')
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.name', 'Produk 06')
            ->assertJsonPath('meta', ['page' => 2, 'per_page' => 5, 'total' => 12, 'last_page' => 3]);

        $this->get('/products?page=3&per_page=5')
            ->assertInertia(fn ($page) => $page->component('Products')
                ->has('products.data', 2)
                ->where('products.meta.page', 3));
    }

    public function test_product_search_and_out_of_range_pages(): void
    {
        $this->login();
        Product::factory()->create(['name' => 'Indomie Goreng']);
        Product::factory()->create(['name' => 'Indomie Soto']);
        Product::factory()->create(['name' => 'Kopi Kapal Api']);
        Product::factory()->create(['name' => 'Diskon 50% Sabun']);

        $this->getJson('/api/products?search=indomie')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        // % is a literal character, not a wildcard.
        $this->getJson('/api/products?search=50%25')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Diskon 50% Sabun');

        // Past the last page, or garbage: clamp instead of failing.
        $this->getJson('/api/products?page=99&per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 50);

        $this->get('/products?page=abc')->assertOk();
    }

    public function test_weighed_goods_are_sold_and_reported_by_weight(): void
    {
        $this->login();
        $rice = Product::factory()->create([
            'name' => 'Beras', 'unit' => 'kg', 'stock' => 25000, 'sell_price' => 14000, 'cost_price' => 12000,
        ]);
        $coffee = Product::factory()->create(['name' => 'Kopi', 'stock' => 50, 'sell_price' => 2000, 'cost_price' => 1200]);

        // 1,52 kg rice = Rp 21.280 -> Rp 21.300, plus 3 coffees
        $this->postJson('/api/sales', ['items' => [
            ['product_id' => $rice->id, 'qty' => 1520],
            ['product_id' => $coffee->id, 'qty' => 3],
        ]])->assertCreated()->assertJson(['total' => 27300, 'item_count' => 4]);

        $this->assertSame(23480, $rice->fresh()->stock);
        $this->assertDatabaseHas('transactions', ['product_id' => $rice->id, 'qty' => 1520, 'total' => 21300, 'cost_total' => 18240]);

        $this->getJson('/api/report')
            ->assertJsonPath('summary.revenue', 27300)
            ->assertJsonPath('summary.profit', (21300 - 18240) + (6000 - 3600))
            ->assertJsonPath('summary.items', 4)
            ->assertJsonPath('bestSellers.0.name', 'Beras')
            ->assertJsonPath('bestSellers.0.unit', 'kg')
            ->assertJsonPath('bestSellers.0.qty', 1520);

        $this->postJson('/api/sales', ['items' => [['product_id' => $rice->id, 'qty' => 30000]]])
            ->assertJsonValidationErrors(['items' => 'Stok Beras tinggal 23,48 kg.']);
    }

    public function test_stock_in_and_low_stock_use_the_unit(): void
    {
        $this->login();
        $oil = Product::factory()->create(['name' => 'Minyak Goreng', 'unit' => 'liter', 'stock' => 4500]);
        Product::factory()->create(['name' => 'Beras', 'unit' => 'kg', 'stock' => 6000]);

        $this->getJson('/api/report')
            ->assertJsonCount(1, 'lowStock.data')
            ->assertJsonPath('lowStock.data.0.name', 'Minyak Goreng')
            ->assertJsonPath('lowStock.data.0.unit', 'liter');

        $this->postJson('/api/stock-in', ['product_id' => $oil->id, 'qty' => 20000])
            ->assertCreated()
            ->assertJson(['message' => '+20 liter Minyak Goreng (stok sekarang 24,5 liter).']);

        $this->getJson('/api/stock-in/history')->assertJsonPath('data.0.unit', 'liter');
    }

    public function test_the_unit_is_chosen_once(): void
    {
        $this->login();
        $category = Category::factory()->create();

        $this->postJson('/api/products', [
            'name' => 'Beras', 'category_id' => $category->id, 'unit' => 'kg', 'sell_price' => 14000, 'stock' => 25000,
        ])->assertCreated();

        $rice = Product::firstWhere('name', 'Beras');
        $this->assertSame('kg', $rice->unit);
        $this->assertSame(25000, $rice->stock);

        // Editing without a unit keeps it; switching it is refused.
        $this->putJson("/api/products/{$rice->id}", ['name' => 'Beras', 'category_id' => $category->id, 'sell_price' => 15000])
            ->assertOk();
        $this->putJson("/api/products/{$rice->id}", ['name' => 'Beras', 'category_id' => $category->id, 'unit' => 'pcs', 'sell_price' => 15000])
            ->assertJsonValidationErrors('unit');

        $this->postJson('/api/products', ['name' => 'X', 'category_id' => $category->id, 'unit' => 'ons', 'sell_price' => 1])
            ->assertJsonValidationErrors('unit');
    }

    public function test_packaged_rice_and_oil_are_converted_to_weighed_goods(): void
    {
        $rice = Product::factory()->create(['name' => 'Beras 1kg', 'stock' => 10, 'sell_price' => 14000, 'cost_price' => 12000]);
        $oil = Product::factory()->create(['name' => 'Minyak Goreng 1L', 'stock' => 3]);
        Transaction::create([
            'code' => 'OLD', 'product_id' => $rice->id, 'qty' => 2, 'price' => 14000,
            'cost_price' => 12000, 'cost_total' => 24000, 'total' => 28000, 'sold_at' => now(),
        ]);
        StockIn::create(['product_id' => $rice->id, 'qty' => 5, 'date' => today()]);

        $this->seed(WeighedProductSeeder::class);
        $this->seed(WeighedProductSeeder::class); // safe to run twice

        $rice->refresh();
        $this->assertSame(['Beras', 'kg', 10000], [$rice->name, $rice->unit, $rice->stock]);
        $this->assertSame(['Minyak Goreng', 'liter', 3000], [$oil->fresh()->name, $oil->fresh()->unit, $oil->fresh()->stock]);
        $this->assertDatabaseHas('transactions', ['code' => 'OLD', 'qty' => 2000, 'total' => 28000]);
        $this->assertDatabaseHas('stock_ins', ['product_id' => $rice->id, 'qty' => 5000]);
    }

    public function test_demo_seeder_fills_a_shelf_and_a_day_of_sales(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class); // safe to run twice

        // Without the sample products (rice, eggs, oil) one checkout is skipped.
        $sales = Transaction::whereDate('sold_at', today())->distinct()->count('code');
        $this->assertGreaterThan(50, Product::count());
        $this->assertGreaterThanOrEqual(10, $sales);
        $this->assertSame(0, Product::where('stock', '<', 0)->count());

        $this->login();
        $this->getJson('/api/report')
            ->assertJsonPath('summary.sales', $sales)
            ->assertJsonCount(5, 'bestSellers')
            ->assertJsonPath('history.meta.total', $sales)
            ->assertJsonCount(10, 'history.data');

        $this->getJson('/api/report?history_page=2')->assertJsonCount($sales - 10, 'history.data');
    }

    public function test_cashier_products_are_paginated_and_filtered_on_the_server(): void
    {
        $this->login();
        $drinks = Category::factory()->create(['name' => 'Minuman']);
        Product::factory()->count(25)->create();
        Product::factory()->create(['name' => 'Teh Botol', 'category_id' => $drinks->id]);

        $this->getJson('/api/cashier/products')
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 26)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson("/api/cashier/products?category={$drinks->id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Teh Botol');

        $this->getJson('/api/cashier/products?search=teh')->assertJsonPath('data.0.name', 'Teh Botol');

        $this->get('/')->assertInertia(fn ($page) => $page->component('Cashier')
            ->has('products.data', 20)
            ->has('categories'));
    }

    public function test_report_low_stock_is_paginated(): void
    {
        $this->login();
        Product::factory()->count(12)->create(['stock' => 1]);

        $this->getJson('/api/report')
            ->assertJsonCount(10, 'lowStock.data')
            ->assertJsonPath('lowStock.meta.total', 12);

        $this->getJson('/api/report?low_page=2')
            ->assertJsonCount(2, 'lowStock.data')
            ->assertJsonPath('lowStock.meta.page', 2);
    }
}

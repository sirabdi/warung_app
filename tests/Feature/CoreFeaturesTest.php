<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\StockIn;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use App\Infrastructure\Persistence\Eloquent\Models\User;
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

        $this->postJson('/api/products', ['name' => 'Indomie', 'sell_price' => 3500, 'stock' => 10])
            ->assertCreated()
            ->assertJson(['message' => 'Indomie ditambahkan.']);

        $product = Product::firstWhere('name', 'Indomie');
        $this->assertSame(3500, $product->sell_price);
        $this->assertSame(10, $product->stock);

        $this->putJson("/api/products/{$product->id}", ['name' => 'Indomie Goreng', 'sell_price' => 4000, 'cost_price' => 2800])
            ->assertOk();

        $product->refresh();
        $this->assertSame('Indomie Goreng', $product->name);
        $this->assertSame(4000, $product->sell_price);
        $this->assertSame(10, $product->stock, 'stock must not change from the product form');
    }

    public function test_product_names_must_be_unique(): void
    {
        $this->login();
        Product::factory()->create(['name' => 'Aqua']);

        $this->postJson('/api/products', ['name' => 'Aqua', 'sell_price' => 4000])
            ->assertJsonValidationErrors('name');
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
        Product::factory()->create(['name' => 'Kopi', 'sell_price' => 2000, 'stock' => 9]);

        $this->getJson('/api/cashier/products')
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Kopi')
            ->assertJsonPath('products.0.sell_price', 2000)
            ->assertJsonPath('products.0.stock', 9);
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
                ->where('lowStock.0.name', 'Gula')
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
            ->assertInertia(fn ($page) => $page->component('StockIn')->where('history.0.qty', 6));

        $this->getJson('/api/stock-in/history')->assertJsonPath('history.0.qty', 6);
    }
}

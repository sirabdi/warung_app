<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** With hundreds of products, typing one in again must not make a second copy. */
class DuplicateProductTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->category = Category::factory()->create();
    }

    private function add(string $name)
    {
        return $this->postJson('/api/products', ['name' => $name, 'category_id' => $this->category->id, 'sell_price' => 3500]);
    }

    public function test_case_and_extra_spaces_do_not_make_a_new_product(): void
    {
        $this->add('Indomie Goreng')->assertCreated();

        $this->add('  indomie   GORENG ')
            ->assertJsonValidationErrors(['name' => 'Produk dengan nama indomie GORENG sudah ada.']);
        $this->assertSame(1, Product::count());
    }

    public function test_names_are_saved_with_single_spaces(): void
    {
        $this->add('Aqua   600ml ')->assertCreated();

        $this->assertSame('Aqua 600ml', Product::sole()->name);
    }

    public function test_renaming_onto_another_product_is_rejected(): void
    {
        Product::factory()->create(['name' => 'Aqua 600ml']);
        $teh = Product::factory()->create(['name' => 'Teh Pucuk']);

        $this->putJson("/api/products/{$teh->id}", ['name' => 'AQUA 600ML', 'category_id' => $this->category->id, 'sell_price' => 4000])
            ->assertJsonValidationErrors('name');

        // Changing only the case of its own name is fine.
        $this->putJson("/api/products/{$teh->id}", ['name' => 'TEH PUCUK', 'category_id' => $this->category->id, 'sell_price' => 4000])
            ->assertOk();
    }

    public function test_the_database_refuses_a_second_copy(): void
    {
        Product::factory()->create(['name' => 'Gula 1kg']);

        $this->expectException(UniqueConstraintViolationException::class);
        Product::factory()->create(['name' => 'Gula 1kg']);
    }

    public function test_similar_products_are_offered_while_typing(): void
    {
        Product::factory()->create(['name' => 'Aqua 600ml', 'stock' => 24]);
        Product::factory()->create(['name' => 'Aqua 1500ml']);
        Product::factory()->create(['name' => 'Teh Pucuk 350ml']);

        $this->getJson('/api/products/similar?name='.urlencode('aqua  600ml'))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Aqua 600ml')
            ->assertJsonPath('data.0.exact', true)
            ->assertJsonPath('data.0.stock', 24);

        // Spelled apart, it is not the same name but still shows up first.
        $this->getJson('/api/products/similar?name='.urlencode('Aqua 600 ml'))
            ->assertJsonPath('data.0.name', 'Aqua 600ml')
            ->assertJsonPath('data.0.exact', false);

        $this->getJson('/api/products/similar?name=Sabun')->assertExactJson(['data' => []]);
        $this->getJson('/api/products/similar?name=a')->assertExactJson(['data' => []]);
    }

    public function test_similar_leaves_out_the_product_being_edited(): void
    {
        $aqua = Product::factory()->create(['name' => 'Aqua 600ml']);

        $this->getJson("/api/products/similar?name=Aqua+600ml&except={$aqua->id}")->assertExactJson(['data' => []]);
    }

    public function test_similar_only_looks_in_the_own_store(): void
    {
        $this->actingAs(User::factory()->create());
        Product::factory()->create(['name' => 'Kopi Budi']);

        $this->actingAs(User::factory()->create());
        $this->getJson('/api/products/similar?name=Kopi+Budi')->assertExactJson(['data' => []]);
    }

    public function test_add_stock_opens_stock_in_on_that_product(): void
    {
        $aqua = Product::factory()->create(['name' => 'Aqua 600ml', 'stock' => 24]);

        $this->get("/stock-in?product={$aqua->id}")->assertInertia(fn ($page) => $page
            ->component('StockIn')
            ->where('selectedProduct.name', 'Aqua 600ml')
            ->where('selectedProduct.stock', 24));

        $this->get('/stock-in?product=99999')->assertInertia(fn ($page) => $page->where('selectedProduct', null));
    }

    public function test_add_and_edit_have_their_own_pages(): void
    {
        $aqua = Product::factory()->create(['name' => 'Aqua 600ml', 'category_id' => $this->category->id]);

        $this->get('/products/create')->assertOk()->assertInertia(fn ($page) => $page
            ->component('ProductForm')
            ->where('product', null)
            ->has('categories', 1));

        $this->get("/products/{$aqua->id}/edit")->assertOk()->assertInertia(fn ($page) => $page
            ->component('ProductForm')
            ->where('product.name', 'Aqua 600ml')
            ->where('product.category_id', $this->category->id));

        $this->get('/products/99999/edit')->assertNotFound();
    }

    public function test_another_stores_product_cannot_be_opened_for_editing(): void
    {
        $this->actingAs(User::factory()->create());
        $other = Product::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->get("/products/{$other->id}/edit")->assertNotFound();
    }
}

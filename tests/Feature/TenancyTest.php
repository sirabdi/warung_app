<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Two customers on the same app must never see or touch each other's data. */
class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private User $ani;

    private User $budi;

    private Product $budisCoffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ani = User::factory()->create();
        $this->budi = User::factory()->create();

        $this->actingAs($this->budi);
        $this->budisCoffee = Product::factory()->create(['name' => 'Kopi Budi', 'stock' => 10]);

        $this->actingAs($this->ani);
        Product::factory()->create(['name' => 'Teh Ani']);
    }

    public function test_lists_only_show_the_own_store(): void
    {
        $this->getJson('/api/products')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Teh Ani');
        $this->getJson('/api/cashier/products')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/categories')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/products?search=Kopi')->assertJsonPath('meta.total', 0);
    }

    public function test_another_stores_product_cannot_be_sold_restocked_or_edited(): void
    {
        $id = $this->budisCoffee->id;

        $this->postJson('/api/sales', ['items' => [['product_id' => $id, 'qty' => 1]]])
            ->assertJsonValidationErrors('items.0.product_id');
        $this->postJson('/api/stock-in', ['product_id' => $id, 'qty' => 5])
            ->assertJsonValidationErrors('product_id');
        $this->putJson("/api/products/{$id}", ['name' => 'Diambil', 'category_id' => Category::first()->id, 'sell_price' => 1])
            ->assertStatus(422);

        $this->actingAs($this->budi);
        $this->assertSame(['Kopi Budi', 10], [$this->budisCoffee->fresh()->name, $this->budisCoffee->fresh()->stock]);
    }

    public function test_another_stores_category_cannot_be_used_or_deleted(): void
    {
        $this->actingAs($this->budi);
        $budisCategory = $this->budisCoffee->category_id;

        $this->actingAs($this->ani);
        $this->postJson('/api/products', ['name' => 'X', 'category_id' => $budisCategory, 'sell_price' => 1000])
            ->assertJsonValidationErrors('category_id');
        $this->deleteJson("/api/categories/{$budisCategory}")->assertJsonValidationErrors('category_id');
    }

    public function test_names_only_need_to_be_unique_within_a_store(): void
    {
        $this->postJson('/api/categories', ['name' => 'Minuman'])->assertCreated();
        $this->postJson('/api/products', ['name' => 'Kopi Budi', 'category_id' => Category::firstWhere('name', 'Minuman')->id, 'sell_price' => 3000])
            ->assertCreated();

        $this->actingAs($this->budi);
        $this->postJson('/api/categories', ['name' => 'Minuman'])->assertCreated();
    }

    public function test_reports_count_only_the_own_sales(): void
    {
        $this->actingAs($this->budi);
        $this->postJson('/api/sales', ['items' => [['product_id' => $this->budisCoffee->id, 'qty' => 2]]])->assertCreated();

        $this->actingAs($this->ani);
        $this->getJson('/api/report')->assertJsonPath('summary.sales', 0);
        $this->getJson('/api/stock-in/history')->assertJsonPath('meta.total', 0);
    }
}

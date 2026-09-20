<?php

namespace Tests\Unit\Domain;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\InsufficientStock;
use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\Shared\ValueObject\Money;
use PHPUnit\Framework\TestCase;

/** Pure domain test: no Laravel, no database. */
class ProductTest extends TestCase
{
    private function product(int $stock = 10): Product
    {
        return Product::reconstitute(1, 'Indomie', Money::of(3500), Money::of(2800), $stock);
    }

    public function test_stock_goes_up_and_down(): void
    {
        $product = $this->product(10);

        $product->addStock(5);
        $product->reduceStock(3);

        $this->assertSame(12, $product->stock());
    }

    public function test_cannot_sell_more_than_available_stock(): void
    {
        $product = $this->product(2);

        $this->expectException(InsufficientStock::class);
        $this->expectExceptionMessage('Stok Indomie tinggal 2.');

        $product->reduceStock(3);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $this->expectException(InvalidValue::class);

        $this->product()->reduceStock(0);
    }

    public function test_blank_name_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);

        Product::register('   ', Money::of(3000));
    }

    public function test_new_product_may_start_with_stock(): void
    {
        $product = Product::register('Aqua', Money::of(4000), Money::of(3000), 24);

        $this->assertNull($product->id());
        $this->assertSame(24, $product->stock());
        $this->assertSame(4000, $product->sellPrice()->amount);
    }

    public function test_low_stock_uses_the_threshold(): void
    {
        $this->assertTrue($this->product(5)->isLowStock(5));
        $this->assertFalse($this->product(6)->isLowStock(5));
    }
}

<?php

namespace Tests\Unit\Domain;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\InsufficientStock;
use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;
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

        Product::register('   ', 1, Money::of(3000));
    }

    public function test_new_product_may_start_with_stock(): void
    {
        $product = Product::register('Aqua', 3, Money::of(4000), Money::of(3000), 24);

        $this->assertNull($product->id());
        $this->assertSame(3, $product->categoryId());
        $this->assertSame(24, $product->stock());
        $this->assertSame(4000, $product->sellPrice()->amount);
    }

    public function test_a_product_must_have_a_category(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Kategori wajib dipilih.');

        Product::register('Aqua', 0, Money::of(4000));
    }

    public function test_updating_details_can_move_the_category(): void
    {
        $product = $this->product();

        $product->updateDetails('Indomie Goreng', 7, Money::of(3500));

        $this->assertSame(7, $product->categoryId());
        $this->assertSame(10, $product->stock(), 'stock is not part of the details');
    }

    public function test_low_stock_uses_the_threshold(): void
    {
        $this->assertTrue($this->product(5)->isLowStock(5));
        $this->assertFalse($this->product(6)->isLowStock(5));
    }

    public function test_weighed_stock_is_kept_in_grams(): void
    {
        $rice = Product::register('Beras', 1, Money::of(14000), Money::of(12000), 25000, Unit::Kilogram);

        $rice->reduceStock(1520);

        $this->assertSame(23480, $rice->stock());
        $this->assertFalse($rice->isLowStock(5), '23,48 kg is above 5 kg');
        $this->assertTrue(Product::reconstitute(2, 'Minyak', Money::of(18000), Money::zero(), 4999, 1, Unit::Liter)->isLowStock(5));
    }

    public function test_shortage_message_shows_the_unit(): void
    {
        $rice = Product::reconstitute(1, 'Beras', Money::of(14000), Money::zero(), 1500, 1, Unit::Kilogram);

        $this->expectException(InsufficientStock::class);
        $this->expectExceptionMessage('Stok Beras tinggal 1,5 kg.');

        $rice->reduceStock(2000);
    }

    public function test_the_unit_cannot_change_after_creation(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Satuan tidak bisa diganti');

        $this->product()->updateDetails('Indomie', 1, Money::of(3500), null, Unit::Kilogram);
    }
}

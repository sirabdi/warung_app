<?php

namespace Tests\Unit\Domain;

use App\Domain\Product\Entity\Product;
use App\Domain\Sale\Entity\Sale;
use App\Domain\Sale\Exception\EmptyCart;
use App\Domain\Sale\ValueObject\SaleCode;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SaleTest extends TestCase
{
    private function sale(): Sale
    {
        $soldAt = new DateTimeImmutable('2026-09-20 08:00:00');

        return Sale::start(SaleCode::for($soldAt), $soldAt, cashierId: 1);
    }

    public function test_selling_reduces_stock_and_sums_the_total(): void
    {
        $coffee = Product::reconstitute(1, 'Kopi', Money::of(2000), Money::of(1200), 50);
        $tea = Product::reconstitute(2, 'Teh', Money::of(5000), Money::of(3500), 10);

        $sale = $this->sale();
        $sale->sell($coffee, 3);
        $sale->sell($tea, 1);
        $sale->complete();

        $this->assertSame(47, $coffee->stock());
        $this->assertSame(9, $tea->stock());
        $this->assertSame(11000, $sale->total()->amount);
        $this->assertSame(4, $sale->itemCount());
    }

    public function test_prices_are_copied_at_sale_time(): void
    {
        $coffee = Product::reconstitute(1, 'Kopi', Money::of(2000), Money::of(1200), 50);

        $sale = $this->sale();
        $sale->sell($coffee, 2);

        // The product's price goes up after the sale was recorded.
        $coffee->updateDetails('Kopi', 1, Money::of(3000), Money::of(1500));

        $item = $sale->items()[0];
        $this->assertSame(2000, $item->price->amount);
        $this->assertSame(1600, $item->grossProfit()->amount);
        $this->assertSame(4000, $sale->total()->amount);
    }

    public function test_weighed_goods_are_priced_by_weight(): void
    {
        $rice = Product::reconstitute(1, 'Beras', Money::of(14000), Money::of(12000), 25000, 1, Unit::Kilogram);
        $coffee = Product::reconstitute(2, 'Kopi', Money::of(2000), Money::of(1200), 50);

        $sale = $this->sale();
        $sale->sell($rice, 1520);
        $sale->sell($coffee, 3);

        $this->assertSame(23480, $rice->stock());
        $this->assertSame(21300 + 6000, $sale->total()->amount);
        $this->assertSame(21300 - 18240, $sale->items()[0]->grossProfit()->amount);
        $this->assertSame(4, $sale->itemCount(), '3 coffees + 1 weighed line');
    }

    public function test_an_empty_cart_cannot_be_completed(): void
    {
        $this->expectException(EmptyCart::class);

        $this->sale()->complete();
    }

    public function test_sale_code_contains_the_time(): void
    {
        $code = (string) SaleCode::for(new DateTimeImmutable('2026-09-20 08:15:30'));

        $this->assertStringStartsWith('260920081530', $code);
        $this->assertSame(16, strlen($code));
    }
}

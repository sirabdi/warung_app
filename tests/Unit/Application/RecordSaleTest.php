<?php

namespace Tests\Unit\Application;

use App\Application\Cashier\DTO\Cart;
use App\Application\Cashier\UseCase\RecordSale;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\InsufficientStock;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\FixedClock;
use Tests\Support\ImmediateTransactionManager;
use Tests\Support\InMemoryProductRepository;
use Tests\Support\InMemorySaleRepository;

/** The use case is tested against in-memory repositories — no database involved. */
class RecordSaleTest extends TestCase
{
    private InMemoryProductRepository $products;

    private InMemorySaleRepository $sales;

    private RecordSale $recordSale;

    protected function setUp(): void
    {
        $this->products = new InMemoryProductRepository;
        $this->sales = new InMemorySaleRepository;

        $this->recordSale = new RecordSale(
            $this->products,
            $this->sales,
            new ImmediateTransactionManager,
            new FixedClock(new DateTimeImmutable('2026-09-20 09:30:00')),
        );
    }

    private function product(string $name, int $sellPrice, int $stock): Product
    {
        return $this->products->add(Product::register($name, Money::of($sellPrice), Money::of(1000), $stock));
    }

    public function test_a_sale_reduces_stock_and_returns_the_total(): void
    {
        $coffee = $this->product('Kopi', 2000, 10);
        $tea = $this->product('Teh', 5000, 10);

        $result = $this->recordSale->execute(Cart::fromArray([
            ['product_id' => $coffee->storedId(), 'qty' => 2],
            ['product_id' => $tea->storedId(), 'qty' => 1],
        ]), cashierId: 7);

        $this->assertSame(9000, $result->total->amount);
        $this->assertSame(3, $result->itemCount);
        $this->assertSame(8, $this->products->lock($coffee->storedId())->stock());
        $this->assertCount(1, $this->sales->saved);
        $this->assertSame(7, $this->sales->saved[0]->cashierId());
    }

    public function test_insufficient_stock_cancels_the_whole_sale(): void
    {
        $rice = $this->product('Beras', 14000, 1);

        $this->expectException(InsufficientStock::class);

        try {
            $this->recordSale->execute(Cart::fromArray([
                ['product_id' => $rice->storedId(), 'qty' => 3],
            ]));
        } finally {
            $this->assertSame([], $this->sales->saved);
        }
    }

    public function test_duplicate_products_in_the_cart_are_merged(): void
    {
        $coffee = $this->product('Kopi', 2000, 10);

        $result = $this->recordSale->execute(Cart::fromArray([
            ['product_id' => $coffee->storedId(), 'qty' => 2],
            ['product_id' => $coffee->storedId(), 'qty' => 3],
        ]));

        $this->assertSame(10000, $result->total->amount);
        $this->assertSame(5, $this->products->lock($coffee->storedId())->stock());
    }
}

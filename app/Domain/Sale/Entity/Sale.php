<?php

namespace App\Domain\Sale\Entity;

use App\Domain\Product\Entity\Product;
use App\Domain\Sale\Exception\EmptyCart;
use App\Domain\Sale\ValueObject\SaleCode;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;

/**
 * Sale aggregate root.
 *
 * sell() also reduces the product's stock: there is no way to add a sale line
 * without the stock going down with it.
 */
final class Sale
{
    /** @var list<SaleItem> */
    private array $items = [];

    private function __construct(
        private readonly SaleCode $code,
        private readonly DateTimeImmutable $soldAt,
        private readonly ?int $cashierId,
    ) {}

    public static function start(SaleCode $code, DateTimeImmutable $soldAt, ?int $cashierId = null): self
    {
        return new self($code, $soldAt, $cashierId);
    }

    public function sell(Product $product, int $qty): void
    {
        $product->reduceStock($qty);

        $this->items[] = SaleItem::for($product, $qty);
    }

    /** @throws EmptyCart */
    public function complete(): void
    {
        if ($this->items === []) {
            throw new EmptyCart;
        }
    }

    public function total(): Money
    {
        return array_reduce(
            $this->items,
            fn (Money $sum, SaleItem $item) => $sum->plus($item->total()),
            Money::zero(),
        );
    }

    public function itemCount(): int
    {
        return array_sum(array_map(fn (SaleItem $item) => $item->itemCount(), $this->items));
    }

    public function code(): SaleCode
    {
        return $this->code;
    }

    public function soldAt(): DateTimeImmutable
    {
        return $this->soldAt;
    }

    public function cashierId(): ?int
    {
        return $this->cashierId;
    }

    /** @return list<SaleItem> */
    public function items(): array
    {
        return $this->items;
    }
}

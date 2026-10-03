<?php

namespace App\Domain\Sale\Entity;

use App\Domain\Product\Entity\Product;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;

/**
 * One product within one sale.
 *
 * Sell price and cost price are copied here (instead of being read from the
 * product later) so past reports do not change when a price is edited.
 *
 * $qty is in steps of the unit (pieces, grams or ml); prices are per unit.
 */
final readonly class SaleItem
{
    private function __construct(
        public int $productId,
        public string $productName,
        public int $qty,
        public Unit $unit,
        public Money $price,
        public Money $costPrice,
    ) {}

    public static function for(Product $product, int $qty): self
    {
        return new self(
            $product->storedId(),
            $product->name(),
            $qty,
            $product->unit(),
            $product->sellPrice(),
            $product->costPrice(),
        );
    }

    public function total(): Money
    {
        return $this->unit->charge($this->price, $this->qty);
    }

    public function costTotal(): Money
    {
        return $this->unit->value($this->costPrice, $this->qty);
    }

    public function grossProfit(): Money
    {
        return $this->total()->minus($this->costTotal());
    }

    public function itemCount(): int
    {
        return $this->unit->itemCount($this->qty);
    }
}

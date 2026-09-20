<?php

namespace App\Domain\Sale\Entity;

use App\Domain\Product\Entity\Product;
use App\Domain\Shared\ValueObject\Money;

/**
 * One product within one sale.
 *
 * Sell price and cost price are copied here (instead of being read from the
 * product later) so past reports do not change when a price is edited.
 */
final readonly class SaleItem
{
    private function __construct(
        public int $productId,
        public string $productName,
        public int $qty,
        public Money $price,
        public Money $costPrice,
    ) {}

    public static function for(Product $product, int $qty): self
    {
        return new self(
            $product->storedId(),
            $product->name(),
            $qty,
            $product->sellPrice(),
            $product->costPrice(),
        );
    }

    public function total(): Money
    {
        return $this->price->times($this->qty);
    }

    public function grossProfit(): Money
    {
        return $this->total()->minus($this->costPrice->times($this->qty));
    }
}

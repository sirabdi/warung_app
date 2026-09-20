<?php

namespace App\Domain\Inventory\Entity;

use App\Domain\Product\Entity\Product;
use DateTimeImmutable;

/**
 * A goods-received record. Created through record() so the product's stock
 * always grows together with the record itself.
 */
final readonly class StockIn
{
    private function __construct(
        public int $productId,
        public string $productName,
        public int $qty,
        public DateTimeImmutable $date,
        public ?int $recordedBy,
    ) {}

    public static function record(Product $product, int $qty, DateTimeImmutable $date, ?int $recordedBy = null): self
    {
        $product->addStock($qty);

        return new self($product->storedId(), $product->name(), $qty, $date, $recordedBy);
    }
}

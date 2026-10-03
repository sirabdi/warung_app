<?php

namespace App\Application\Inventory\DTO;

use App\Domain\Shared\ValueObject\Unit;

/** Quantities in steps of $unit (pieces, grams or ml). */
final readonly class StockInResult
{
    public function __construct(
        public string $productName,
        public int $qty,
        public int $currentStock,
        public Unit $unit = Unit::Piece,
    ) {}
}

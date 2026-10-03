<?php

namespace App\Application\Product\DTO;

use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;

final readonly class ProductData
{
    public function __construct(
        public string $name,
        public int $categoryId,
        public Money $sellPrice,
        public Money $costPrice,
        public int $initialStock = 0,
        // null on edit = keep the product's unit
        public ?Unit $unit = null,
    ) {}

    /**
     * Prices are per unit (per pcs, kg or liter); stock is in steps of the unit
     * (pieces, grams or ml).
     *
     * @param  array{name: string, category_id: int|string, unit?: string|null, sell_price: int|string, cost_price?: int|string|null, stock?: int|string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            (int) $data['category_id'],
            Money::of((int) $data['sell_price']),
            Money::of((int) ($data['cost_price'] ?? 0)),
            (int) ($data['stock'] ?? 0),
            isset($data['unit']) ? Unit::from($data['unit']) : null,
        );
    }
}

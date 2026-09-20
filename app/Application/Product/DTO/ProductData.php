<?php

namespace App\Application\Product\DTO;

use App\Domain\Shared\ValueObject\Money;

final readonly class ProductData
{
    public function __construct(
        public string $name,
        public Money $sellPrice,
        public Money $costPrice,
        public int $initialStock = 0,
    ) {}

    /** @param array{name: string, sell_price: int|string, cost_price?: int|string|null, stock?: int|string|null} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            Money::of((int) $data['sell_price']),
            Money::of((int) ($data['cost_price'] ?? 0)),
            (int) ($data['stock'] ?? 0),
        );
    }
}

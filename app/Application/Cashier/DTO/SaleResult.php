<?php

namespace App\Application\Cashier\DTO;

use App\Domain\Shared\ValueObject\Money;

final readonly class SaleResult
{
    public function __construct(public string $code, public Money $total, public int $itemCount) {}
}

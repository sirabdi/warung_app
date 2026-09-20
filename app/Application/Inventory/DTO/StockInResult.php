<?php

namespace App\Application\Inventory\DTO;

final readonly class StockInResult
{
    public function __construct(public string $productName, public int $qty, public int $currentStock) {}
}

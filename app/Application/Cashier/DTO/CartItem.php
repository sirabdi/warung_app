<?php

namespace App\Application\Cashier\DTO;

final readonly class CartItem
{
    public function __construct(public int $productId, public int $qty) {}
}

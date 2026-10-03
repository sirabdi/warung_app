<?php

namespace App\Application\Cashier\DTO;

/** $qty is in steps of the product's unit: pieces, grams or ml. */
final readonly class CartItem
{
    public function __construct(public int $productId, public int $qty) {}
}

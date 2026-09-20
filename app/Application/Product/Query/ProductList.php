<?php

namespace App\Application\Product\Query;

interface ProductList
{
    /** @return list<array{id: int, name: string, cost_price: int, sell_price: int, stock: int}> */
    public function all(): array;
}

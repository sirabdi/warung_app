<?php

namespace App\Application\Cashier\Query;

/**
 * Read model for the cashier page. Kept separate from the repository because
 * the need differs: the till wants a ready-to-render list (already sorted by
 * popularity), not an aggregate.
 */
interface ProductsForCashier
{
    /**
     * Best sellers first so they are the fastest to find.
     *
     * @return list<array{id: int, name: string, sell_price: int, stock: int}>
     */
    public function get(): array;
}

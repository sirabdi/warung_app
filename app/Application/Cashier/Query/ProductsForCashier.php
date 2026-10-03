<?php

namespace App\Application\Cashier\Query;

use App\Application\Shared\Query\Page;

/**
 * Read model for the cashier page. Kept separate from the repository because
 * the need differs: the till wants a ready-to-render list (already sorted by
 * popularity), not an aggregate.
 */
interface ProductsForCashier
{
    /**
     * Best sellers of the last 30 days first so they are the fastest to find,
     * optionally narrowed by part of the name and by category.
     *
     * @return Page<array{id: int, name: string, category_id: ?int, category_name: ?string, unit: string, sell_price: int, stock: int}>
     */
    public function paginate(string $search, ?int $categoryId, int $page, int $perPage): Page;
}

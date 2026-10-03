<?php

namespace App\Application\Product\Query;

use App\Application\Shared\Query\Page;

interface ProductList
{
    /**
     * Products ordered by name, optionally filtered by a part of the name and
     * by category.
     *
     * @return Page<array{id: int, name: string, category_id: ?int, category_name: ?string, unit: string, cost_price: int, sell_price: int, stock: int}>
     */
    public function paginate(string $search, int $page, int $perPage, ?int $categoryId = null): Page;
}

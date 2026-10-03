<?php

namespace App\Application\Product\Query;

use App\Application\Shared\Query\Page;

/** @phpstan-type ProductRow array{id: int, name: string, category_id: ?int, category_name: ?string, unit: string, cost_price: int, sell_price: int, stock: int} */
interface ProductList
{
    /**
     * Products ordered by name, optionally filtered by a part of the name and
     * by category.
     *
     * @return Page<ProductRow>
     */
    public function paginate(string $search, int $page, int $perPage, ?int $categoryId = null): Page;

    /**
     * Products a new name may be a duplicate of, so the owner sees them while
     * typing. A product counts when it contains at least half of the typed
     * words; the same name (Product::nameKey) comes first with exact = true.
     *
     * @return list<ProductRow&array{exact: bool}>
     */
    public function similar(string $name, ?int $exceptId = null, int $limit = 5): array;

    /** @return ProductRow|null */
    public function find(int $id): ?array;
}

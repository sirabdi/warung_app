<?php

namespace App\Application\Category\Query;

use App\Application\Shared\Query\Page;

interface CategoryList
{
    /**
     * Every category by name, with how many products sit in it — for the
     * category pickers, which need all of them at once.
     *
     * @return list<array{id: int, name: string, products_count: int}>
     */
    public function all(): array;

    /**
     * The same rows one page at a time, for the category management page.
     *
     * @return Page<array{id: int, name: string, products_count: int}>
     */
    public function paginate(int $page, int $perPage): Page;
}

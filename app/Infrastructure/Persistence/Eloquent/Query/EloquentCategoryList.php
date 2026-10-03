<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Category\Query\CategoryList;
use App\Application\Shared\Query\Page;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use Illuminate\Database\Eloquent\Builder;

final class EloquentCategoryList implements CategoryList
{
    use PaginatesQueries;

    public function all(): array
    {
        return $this->query()->get()->map($this->row(...))->all();
    }

    public function paginate(int $page, int $perPage): Page
    {
        return $this->page($this->query(), $page, $perPage, ['*'], $this->row(...));
    }

    private function query(): Builder
    {
        return Category::query()
            ->select(['id', 'name'])
            ->withCount('products')
            ->orderBy('name')
            ->orderBy('id');
    }

    /** @return array{id: int, name: string, products_count: int} */
    private function row(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'products_count' => $category->products_count,
        ];
    }
}

<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Product\Query\ProductList;
use App\Application\Shared\Query\Page;
use App\Infrastructure\Persistence\Eloquent\Models\Product;

final class EloquentProductList implements ProductList
{
    use PaginatesQueries;

    public function paginate(string $search, int $page, int $perPage, ?int $categoryId = null): Page
    {
        $query = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->when($search !== '', fn ($query) => $this->whereContains($query, 'products.name', $search))
            ->when($categoryId, fn ($query) => $query->where('products.category_id', $categoryId))
            ->orderBy('products.name')
            ->orderBy('products.id');

        $columns = [
            'products.id', 'products.name', 'products.category_id', 'categories.name as category_name', 'products.unit',
            'products.cost_price', 'products.sell_price', 'products.stock',
        ];

        return $this->page($query, $page, $perPage, $columns, fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'category_name' => $product->category_name,
            'unit' => $product->unit,
            'cost_price' => $product->cost_price,
            'sell_price' => $product->sell_price,
            'stock' => $product->stock,
        ]);
    }
}

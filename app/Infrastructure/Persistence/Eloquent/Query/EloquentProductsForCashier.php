<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Cashier\Query\ProductsForCashier;
use App\Application\Shared\Query\Page;
use App\Infrastructure\Persistence\Eloquent\Models\Product;

final class EloquentProductsForCashier implements ProductsForCashier
{
    use PaginatesQueries;

    public function paginate(string $search, ?int $categoryId, int $page, int $perPage): Page
    {
        $query = Product::query()
            ->select(['id', 'name', 'category_id', 'unit', 'sell_price', 'stock'])
            ->with('category:id,name')
            ->when($search !== '', fn ($query) => $this->whereContains($query, 'name', $search))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            // Best sellers of the last 30 days come first.
            ->withSum(['transactions as sold' => fn ($query) => $query->where('sold_at', '>=', now()->subDays(30))], 'qty')
            ->orderByDesc('sold')
            ->orderBy('name')
            ->orderBy('id');

        return $this->page($query, $page, $perPage, ['*'], fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'unit' => $product->unit,
            'sell_price' => $product->sell_price,
            'stock' => $product->stock,
        ]);
    }
}

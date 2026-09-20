<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Cashier\Query\ProductsForCashier;
use App\Infrastructure\Persistence\Eloquent\Models\Product;

final class EloquentProductsForCashier implements ProductsForCashier
{
    public function get(): array
    {
        return Product::query()
            ->select(['id', 'name', 'sell_price', 'stock'])
            // Best sellers of the last 30 days come first.
            ->withSum(['transactions as sold' => fn ($query) => $query->where('sold_at', '>=', now()->subDays(30))], 'qty')
            ->orderByDesc('sold')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sell_price' => $product->sell_price,
                'stock' => $product->stock,
            ])
            ->all();
    }
}

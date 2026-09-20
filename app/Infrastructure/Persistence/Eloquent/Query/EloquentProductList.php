<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Product\Query\ProductList;
use App\Infrastructure\Persistence\Eloquent\Models\Product;

final class EloquentProductList implements ProductList
{
    public function all(): array
    {
        return Product::orderBy('name')
            ->get(['id', 'name', 'cost_price', 'sell_price', 'stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'cost_price' => $product->cost_price,
                'sell_price' => $product->sell_price,
                'stock' => $product->stock,
            ])
            ->all();
    }
}

<?php

namespace App\Infrastructure\Persistence\Eloquent\Mapper;

use App\Domain\Product\Entity\Product;
use App\Domain\Shared\ValueObject\Money;
use App\Infrastructure\Persistence\Eloquent\Models\Product as ProductModel;

/** Translates both ways between a products row and the domain entity. */
final class ProductMapper
{
    public static function toDomain(ProductModel $model): Product
    {
        return Product::reconstitute(
            $model->id,
            $model->name,
            Money::of($model->sell_price),
            Money::of($model->cost_price),
            $model->stock,
        );
    }

    /** @return array<string, int|string> */
    public static function toColumns(Product $product): array
    {
        return [
            'name' => $product->name(),
            'sell_price' => $product->sellPrice()->amount,
            'cost_price' => $product->costPrice()->amount,
            'stock' => $product->stock(),
        ];
    }
}

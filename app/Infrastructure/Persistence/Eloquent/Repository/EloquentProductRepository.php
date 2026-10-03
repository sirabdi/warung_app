<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\DuplicateProductName;
use App\Domain\Product\Exception\ProductNotFound;
use App\Domain\Product\Repository\ProductRepository;
use App\Infrastructure\Persistence\Eloquent\Mapper\ProductMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Product as ProductModel;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentProductRepository implements ProductRepository
{
    public function lockMany(array $ids): array
    {
        return ProductModel::whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->mapWithKeys(fn (ProductModel $model) => [$model->id => ProductMapper::toDomain($model)])
            ->all();
    }

    public function lock(int $id): Product
    {
        $model = ProductModel::whereKey($id)->lockForUpdate()->first()
            ?? throw ProductNotFound::withId($id);

        return ProductMapper::toDomain($model);
    }

    public function find(int $id): ?Product
    {
        $model = ProductModel::find($id);

        return $model ? ProductMapper::toDomain($model) : null;
    }

    public function save(Product $product): Product
    {
        // For an existing product the row is already locked within the same
        // transaction, so stock is written as the entity's final value rather
        // than as an increment.
        $model = $product->id() !== null
            ? ProductModel::findOrFail($product->id())
            : new ProductModel;

        try {
            $model->fill(ProductMapper::toColumns($product))->save();
        } catch (UniqueConstraintViolationException) {
            // Saved by someone else between nameExists() and here.
            throw DuplicateProductName::of($product->name());
        }

        $product->assignId($model->id);

        return $product;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        // Stored names are already normalized; lower() keeps SQLite as lenient as MySQL's _ci collation.
        return ProductModel::whereRaw('lower(name) = ?', [Product::nameKey($name)])
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function countInCategory(int $categoryId): int
    {
        return ProductModel::where('category_id', $categoryId)->count();
    }
}

<?php

namespace App\Infrastructure\Persistence\Eloquent\Repository;

use App\Domain\Category\Entity\Category;
use App\Domain\Category\Repository\CategoryRepository;
use App\Infrastructure\Persistence\Eloquent\Mapper\CategoryMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Category as CategoryModel;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function find(int $id): ?Category
    {
        $model = CategoryModel::find($id);

        return $model ? CategoryMapper::toDomain($model) : null;
    }

    public function exists(int $id): bool
    {
        return CategoryModel::whereKey($id)->exists();
    }

    public function save(Category $category): Category
    {
        $model = $category->id() !== null
            ? CategoryModel::findOrFail($category->id())
            : new CategoryModel;

        $model->fill(CategoryMapper::toColumns($category))->save();

        $category->assignId($model->id);

        return $category;
    }

    public function delete(Category $category): void
    {
        CategoryModel::whereKey($category->id())->delete();
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        return CategoryModel::where('name', trim($name))
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}

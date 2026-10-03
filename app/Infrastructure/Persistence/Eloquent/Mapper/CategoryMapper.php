<?php

namespace App\Infrastructure\Persistence\Eloquent\Mapper;

use App\Domain\Category\Entity\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Category as CategoryModel;

/** Translates both ways between a categories row and the domain entity. */
final class CategoryMapper
{
    public static function toDomain(CategoryModel $model): Category
    {
        return Category::reconstitute($model->id, $model->name);
    }

    /** @return array<string, string> */
    public static function toColumns(Category $category): array
    {
        return ['name' => $category->name()];
    }
}

<?php

namespace App\Domain\Category\Repository;

use App\Domain\Category\Entity\Category;

interface CategoryRepository
{
    public function find(int $id): ?Category;

    public function exists(int $id): bool;

    public function save(Category $category): Category;

    public function delete(Category $category): void;

    public function nameExists(string $name, ?int $exceptId = null): bool;
}

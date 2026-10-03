<?php

namespace Tests\Support;

use App\Domain\Category\Entity\Category;
use App\Domain\Category\Repository\CategoryRepository;

final class InMemoryCategoryRepository implements CategoryRepository
{
    /** @var array<int, Category> */
    private array $categories = [];

    private int $nextId = 1;

    public function find(int $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    public function exists(int $id): bool
    {
        return isset($this->categories[$id]);
    }

    public function save(Category $category): Category
    {
        $category->assignId($category->id() ?? $this->nextId++);
        $this->categories[$category->id()] = $category;

        return $category;
    }

    public function delete(Category $category): void
    {
        unset($this->categories[$category->id()]);
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        foreach ($this->categories as $id => $category) {
            if ($category->name() === trim($name) && $id !== $exceptId) {
                return true;
            }
        }

        return false;
    }
}

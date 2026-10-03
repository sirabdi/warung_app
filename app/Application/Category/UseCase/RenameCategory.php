<?php

namespace App\Application\Category\UseCase;

use App\Domain\Category\Entity\Category;
use App\Domain\Category\Exception\CategoryNotFound;
use App\Domain\Category\Exception\DuplicateCategoryName;
use App\Domain\Category\Repository\CategoryRepository;

final readonly class RenameCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function execute(int $id, string $name): Category
    {
        $category = $this->categories->find($id) ?? throw CategoryNotFound::withId($id);
        $category->rename($name);

        if ($this->categories->nameExists($category->name(), $id)) {
            throw DuplicateCategoryName::of($category->name());
        }

        return $this->categories->save($category);
    }
}

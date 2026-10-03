<?php

namespace App\Application\Category\UseCase;

use App\Domain\Category\Entity\Category;
use App\Domain\Category\Exception\DuplicateCategoryName;
use App\Domain\Category\Repository\CategoryRepository;

final readonly class AddCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function execute(string $name): Category
    {
        $category = Category::create($name);

        if ($this->categories->nameExists($category->name())) {
            throw DuplicateCategoryName::of($category->name());
        }

        return $this->categories->save($category);
    }
}

<?php

namespace App\Application\Category\UseCase;

use App\Domain\Category\Entity\Category;
use App\Domain\Category\Exception\CategoryInUse;
use App\Domain\Category\Exception\CategoryNotFound;
use App\Domain\Category\Repository\CategoryRepository;
use App\Domain\Product\Repository\ProductRepository;

/**
 * A category can only go once no product sits in it — otherwise those
 * products would silently lose their category.
 */
final readonly class DeleteCategory
{
    public function __construct(
        private CategoryRepository $categories,
        private ProductRepository $products,
    ) {}

    public function execute(int $id): Category
    {
        $category = $this->categories->find($id) ?? throw CategoryNotFound::withId($id);

        $inUse = $this->products->countInCategory($id);
        if ($inUse > 0) {
            throw CategoryInUse::by($category->name(), $inUse);
        }

        $this->categories->delete($category);

        return $category;
    }
}

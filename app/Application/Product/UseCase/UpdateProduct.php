<?php

namespace App\Application\Product\UseCase;

use App\Application\Product\DTO\ProductData;
use App\Domain\Category\Exception\CategoryNotFound;
use App\Domain\Category\Repository\CategoryRepository;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\DuplicateProductName;
use App\Domain\Product\Exception\ProductNotFound;
use App\Domain\Product\Repository\ProductRepository;

/**
 * Core feature #1: change a name, category and prices.
 *
 * Stock is deliberately left alone here — it may only change through Stock In
 * or the till, so that every movement leaves a trace.
 */
final readonly class UpdateProduct
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
    ) {}

    public function execute(int $id, ProductData $data): Product
    {
        $product = $this->products->find($id) ?? throw ProductNotFound::withId($id);

        if ($this->products->nameExists($data->name, $id)) {
            throw DuplicateProductName::of($data->name);
        }

        if (! $this->categories->exists($data->categoryId)) {
            throw CategoryNotFound::withId($data->categoryId);
        }

        $product->updateDetails($data->name, $data->categoryId, $data->sellPrice, $data->costPrice, $data->unit);

        return $this->products->save($product);
    }
}

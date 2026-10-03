<?php

namespace App\Application\Product\UseCase;

use App\Application\Product\DTO\ProductData;
use App\Domain\Category\Exception\CategoryNotFound;
use App\Domain\Category\Repository\CategoryRepository;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\DuplicateProductName;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Shared\ValueObject\Unit;

/** Core feature #1: add a product and its prices. */
final readonly class AddProduct
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
    ) {}

    public function execute(ProductData $data): Product
    {
        if ($this->products->nameExists($data->name)) {
            throw DuplicateProductName::of($data->name);
        }

        if (! $this->categories->exists($data->categoryId)) {
            throw CategoryNotFound::withId($data->categoryId);
        }

        return $this->products->save(
            Product::register($data->name, $data->categoryId, $data->sellPrice, $data->costPrice, $data->initialStock, $data->unit ?? Unit::Piece),
        );
    }
}

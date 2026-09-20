<?php

namespace App\Application\Product\UseCase;

use App\Application\Product\DTO\ProductData;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\DuplicateProductName;
use App\Domain\Product\Repository\ProductRepository;

/** Core feature #1: add a product and its prices. */
final readonly class AddProduct
{
    public function __construct(private ProductRepository $products) {}

    public function execute(ProductData $data): Product
    {
        if ($this->products->nameExists($data->name)) {
            throw DuplicateProductName::of($data->name);
        }

        return $this->products->save(
            Product::register($data->name, $data->sellPrice, $data->costPrice, $data->initialStock),
        );
    }
}

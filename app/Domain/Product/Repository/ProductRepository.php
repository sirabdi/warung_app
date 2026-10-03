<?php

namespace App\Domain\Product\Repository;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFound;

interface ProductRepository
{
    /**
     * Read products and lock their rows until the transaction ends, so two
     * cashiers cannot sell the same stock.
     *
     * @param  list<int>  $ids
     * @return array<int, Product> keyed by product id
     */
    public function lockMany(array $ids): array;

    /** @throws ProductNotFound */
    public function lock(int $id): Product;

    public function find(int $id): ?Product;

    public function save(Product $product): Product;

    public function nameExists(string $name, ?int $exceptId = null): bool;

    public function countInCategory(int $categoryId): int;
}

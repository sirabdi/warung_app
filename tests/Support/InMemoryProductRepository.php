<?php

namespace Tests\Support;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFound;
use App\Domain\Product\Repository\ProductRepository;

/** Stands in for the database in use case tests — a plain array is enough. */
final class InMemoryProductRepository implements ProductRepository
{
    /** @var array<int, Product> */
    private array $products = [];

    private int $nextId = 1;

    public function add(Product $product): Product
    {
        return $this->save($product);
    }

    public function lockMany(array $ids): array
    {
        return array_filter(
            $this->products,
            fn (int $id) => in_array($id, $ids, strict: true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function lock(int $id): Product
    {
        return $this->products[$id] ?? throw ProductNotFound::withId($id);
    }

    public function find(int $id): ?Product
    {
        return $this->products[$id] ?? null;
    }

    public function save(Product $product): Product
    {
        $product->assignId($product->id() ?? $this->nextId++);
        $this->products[$product->storedId()] = $product;

        return $product;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        foreach ($this->products as $id => $product) {
            if ($product->name() === trim($name) && $id !== $exceptId) {
                return true;
            }
        }

        return false;
    }
}

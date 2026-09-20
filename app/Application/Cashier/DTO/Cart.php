<?php

namespace App\Application\Cashier\DTO;

use App\Domain\Sale\Exception\EmptyCart;

final readonly class Cart
{
    /** @param list<CartItem> $items */
    private function __construct(public array $items) {}

    /**
     * @param  array<int, array{product_id: int|string, qty: int|string}>  $rows
     *
     * @throws EmptyCart
     */
    public static function fromArray(array $rows): self
    {
        $qtyPerProduct = [];

        foreach ($rows as $row) {
            $id = (int) $row['product_id'];
            $qtyPerProduct[$id] = ($qtyPerProduct[$id] ?? 0) + (int) $row['qty'];
        }

        if ($qtyPerProduct === []) {
            throw new EmptyCart;
        }

        $items = [];
        foreach ($qtyPerProduct as $id => $qty) {
            $items[] = new CartItem($id, $qty);
        }

        return new self($items);
    }

    /** @return list<int> */
    public function productIds(): array
    {
        return array_map(fn (CartItem $item) => $item->productId, $this->items);
    }
}

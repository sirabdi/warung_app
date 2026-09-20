<?php

namespace App\Domain\Product\Entity;

use App\Domain\Product\Exception\InsufficientStock;
use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\Shared\ValueObject\Money;

/**
 * Product aggregate root.
 *
 * Every stock change must go through addStock()/reduceStock(), so there is no
 * shortcut that can make stock negative or change it without a reason.
 */
final class Product
{
    private function __construct(
        private ?int $id,
        private string $name,
        private Money $sellPrice,
        private Money $costPrice,
        private int $stock,
    ) {}

    public static function register(string $name, Money $sellPrice, ?Money $costPrice = null, int $initialStock = 0): self
    {
        $product = new self(null, '', $sellPrice, $costPrice ?? Money::zero(), 0);
        $product->renameTo($name);

        if ($initialStock > 0) {
            $product->addStock($initialStock);
        }

        return $product;
    }

    /** Used by repositories to rebuild a product from the database. */
    public static function reconstitute(int $id, string $name, Money $sellPrice, Money $costPrice, int $stock): self
    {
        return new self($id, $name, $sellPrice, $costPrice, $stock);
    }

    public function updateDetails(string $name, Money $sellPrice, ?Money $costPrice = null): void
    {
        $this->renameTo($name);
        $this->sellPrice = $sellPrice;
        $this->costPrice = $costPrice ?? Money::zero();
    }

    public function addStock(int $qty): void
    {
        $this->assertPositiveQty($qty);

        $this->stock += $qty;
    }

    public function reduceStock(int $qty): void
    {
        $this->assertPositiveQty($qty);

        if ($qty > $this->stock) {
            throw InsufficientStock::for($this->name, $this->stock);
        }

        $this->stock -= $qty;
    }

    public function isLowStock(int $threshold): bool
    {
        return $this->stock <= $threshold;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    /** Id of a product guaranteed to be stored; used when building a sale. */
    public function storedId(): int
    {
        return $this->id ?? throw new InvalidValue('Produk belum tersimpan.');
    }

    public function assignId(int $id): void
    {
        $this->id ??= $id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sellPrice(): Money
    {
        return $this->sellPrice;
    }

    public function costPrice(): Money
    {
        return $this->costPrice;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    private function renameTo(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidValue('Nama produk wajib diisi.', 'name');
        }

        if (mb_strlen($name) > 100) {
            throw new InvalidValue('Nama produk maksimal 100 huruf.', 'name');
        }

        $this->name = $name;
    }

    private function assertPositiveQty(int $qty): void
    {
        if ($qty < 1) {
            throw new InvalidValue('Jumlah minimal 1.', 'qty');
        }
    }
}

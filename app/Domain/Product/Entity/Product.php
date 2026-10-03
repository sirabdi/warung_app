<?php

namespace App\Domain\Product\Entity;

use App\Domain\Product\Exception\InsufficientStock;
use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;

/**
 * Product aggregate root.
 *
 * Every stock change must go through addStock()/reduceStock(), so there is no
 * shortcut that can make stock negative or change it without a reason.
 *
 * The category is held by id only (another aggregate). Rows created before
 * categories existed may still have none, but every save through
 * register()/updateDetails() must name one.
 *
 * Stock and every quantity are whole steps of the unit (pieces, grams or ml),
 * and the unit is fixed once the product exists: switching 10 pcs to grams
 * would silently turn 10 bags of rice into 10 grams.
 */
final class Product
{
    private function __construct(
        private ?int $id,
        private string $name,
        private Money $sellPrice,
        private Money $costPrice,
        private int $stock,
        private ?int $categoryId = null,
        private Unit $unit = Unit::Piece,
    ) {}

    public static function register(
        string $name,
        int $categoryId,
        Money $sellPrice,
        ?Money $costPrice = null,
        int $initialStock = 0,
        Unit $unit = Unit::Piece,
    ): self {
        $product = new self(null, '', $sellPrice, $costPrice ?? Money::zero(), 0, null, $unit);
        $product->renameTo($name);
        $product->moveToCategory($categoryId);

        if ($initialStock > 0) {
            $product->addStock($initialStock);
        }

        return $product;
    }

    /** Used by repositories to rebuild a product from the database. */
    public static function reconstitute(
        int $id,
        string $name,
        Money $sellPrice,
        Money $costPrice,
        int $stock,
        ?int $categoryId = null,
        Unit $unit = Unit::Piece,
    ): self {
        return new self($id, $name, $sellPrice, $costPrice, $stock, $categoryId, $unit);
    }

    public function updateDetails(
        string $name,
        int $categoryId,
        Money $sellPrice,
        ?Money $costPrice = null,
        ?Unit $unit = null,
    ): void {
        if ($unit !== null && $unit !== $this->unit) {
            throw new InvalidValue('Satuan tidak bisa diganti setelah produk dibuat. Buat produk baru untuk satuan lain.', 'unit');
        }

        $this->renameTo($name);
        $this->moveToCategory($categoryId);
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
            throw InsufficientStock::for($this->name, $this->unit->format($this->stock));
        }

        $this->stock -= $qty;
    }

    /** $threshold is in display units: 5 means 5 pcs, or 5 kg for rice. */
    public function isLowStock(int $threshold): bool
    {
        return $this->stock <= $threshold * $this->unit->scale();
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

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function categoryId(): ?int
    {
        return $this->categoryId;
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

    private function moveToCategory(int $categoryId): void
    {
        if ($categoryId < 1) {
            throw new InvalidValue('Kategori wajib dipilih.', 'category_id');
        }

        $this->categoryId = $categoryId;
    }

    private function assertPositiveQty(int $qty): void
    {
        if ($qty < 1) {
            throw new InvalidValue('Jumlah minimal 1.', 'qty');
        }
    }
}

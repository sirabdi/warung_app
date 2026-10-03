<?php

namespace App\Domain\Category\Entity;

use App\Domain\Shared\Exception\InvalidValue;

/**
 * Category aggregate root: a named shelf a product sits on.
 *
 * Products point to a category by id only — a category never holds its
 * products, so renaming one never has to load them.
 */
final class Category
{
    public const MAX_NAME_LENGTH = 50;

    private function __construct(
        private ?int $id,
        private string $name,
    ) {}

    public static function create(string $name): self
    {
        $category = new self(null, '');
        $category->rename($name);

        return $category;
    }

    /** Used by repositories to rebuild a category from the database. */
    public static function reconstitute(int $id, string $name): self
    {
        return new self($id, $name);
    }

    public function rename(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidValue('Nama kategori wajib diisi.', 'name');
        }

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidValue('Nama kategori maksimal '.self::MAX_NAME_LENGTH.' huruf.', 'name');
        }

        $this->name = $name;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function assignId(int $id): void
    {
        $this->id ??= $id;
    }

    public function name(): string
    {
        return $this->name;
    }
}

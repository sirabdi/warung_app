<?php

namespace App\Domain\Category\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class CategoryInUse extends DomainRuleViolation
{
    public static function by(string $name, int $productCount): self
    {
        return new self("Kategori {$name} masih dipakai {$productCount} produk. Pindahkan produknya dulu.");
    }

    public function field(): string
    {
        return 'category';
    }
}

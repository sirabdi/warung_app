<?php

namespace App\Domain\Product\Exception;

use App\Domain\Product\Entity\Product;
use App\Domain\Shared\Exception\DomainRuleViolation;

final class DuplicateProductName extends DomainRuleViolation
{
    public static function of(string $name): self
    {
        return new self('Produk dengan nama '.Product::normalizeName($name).' sudah ada.');
    }

    public function field(): string
    {
        return 'name';
    }
}

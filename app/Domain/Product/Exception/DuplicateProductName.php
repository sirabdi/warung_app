<?php

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class DuplicateProductName extends DomainRuleViolation
{
    public static function of(string $name): self
    {
        return new self("Produk dengan nama {$name} sudah ada.");
    }

    public function field(): string
    {
        return 'name';
    }
}

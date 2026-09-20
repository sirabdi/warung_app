<?php

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class ProductNotFound extends DomainRuleViolation
{
    public static function withId(int $id): self
    {
        return new self("Produk #{$id} tidak ditemukan.");
    }

    public function field(): string
    {
        return 'product_id';
    }
}

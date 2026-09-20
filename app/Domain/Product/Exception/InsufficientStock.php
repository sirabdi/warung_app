<?php

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class InsufficientStock extends DomainRuleViolation
{
    public static function for(string $productName, int $available): self
    {
        return new self("Stok {$productName} tinggal {$available}.");
    }

    public function field(): string
    {
        return 'items';
    }
}

<?php

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class InsufficientStock extends DomainRuleViolation
{
    /** @param string $available already formatted with its unit, e.g. "1,5 kg" */
    public static function for(string $productName, string $available): self
    {
        return new self("Stok {$productName} tinggal {$available}.");
    }

    public function field(): string
    {
        return 'items';
    }
}

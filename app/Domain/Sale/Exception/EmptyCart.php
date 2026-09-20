<?php

namespace App\Domain\Sale\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class EmptyCart extends DomainRuleViolation
{
    public function __construct()
    {
        parent::__construct('Keranjang masih kosong.');
    }

    public function field(): string
    {
        return 'items';
    }
}

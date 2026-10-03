<?php

namespace App\Domain\Category\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class DuplicateCategoryName extends DomainRuleViolation
{
    public static function of(string $name): self
    {
        return new self("Kategori dengan nama {$name} sudah ada.");
    }

    public function field(): string
    {
        return 'name';
    }
}

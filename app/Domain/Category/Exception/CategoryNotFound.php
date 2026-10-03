<?php

namespace App\Domain\Category\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class CategoryNotFound extends DomainRuleViolation
{
    public static function withId(int $id): self
    {
        return new self("Kategori #{$id} tidak ditemukan.");
    }

    public function field(): string
    {
        return 'category_id';
    }
}

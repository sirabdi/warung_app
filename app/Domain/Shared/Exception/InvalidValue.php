<?php

namespace App\Domain\Shared\Exception;

final class InvalidValue extends DomainRuleViolation
{
    public function __construct(string $message, private readonly string $field = 'error')
    {
        parent::__construct($message);
    }

    public function field(): string
    {
        return $this->field;
    }
}

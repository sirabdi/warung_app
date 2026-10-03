<?php

namespace App\Infrastructure\Registration;

use App\Domain\Shared\Exception\DomainRuleViolation;

/** Shown on the registration form like any validation error. */
final class RegistrationRejected extends DomainRuleViolation
{
    public function __construct(string $message, private readonly string $field)
    {
        parent::__construct($message);
    }

    public function field(): string
    {
        return $this->field;
    }
}

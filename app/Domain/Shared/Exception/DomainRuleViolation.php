<?php

namespace App\Domain\Shared\Exception;

use DomainException;

/**
 * Parent of every business rule violation.
 *
 * The HTTP layer turns these into validation errors (see bootstrap/app.php),
 * so the domain never needs to know about requests or responses.
 *
 * Messages stay in Indonesian: they are shown to the shop owner as-is.
 */
abstract class DomainRuleViolation extends DomainException
{
    /** Form field this message belongs to. */
    public function field(): string
    {
        return 'error';
    }
}

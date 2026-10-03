<?php

namespace App\Domain\Subscription\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class PaymentNotFound extends DomainRuleViolation
{
    public static function withExternalId(string $externalId): self
    {
        return new self("Pembayaran {$externalId} tidak ditemukan.");
    }
}

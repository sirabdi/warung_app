<?php

namespace App\Domain\Subscription\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;
use App\Domain\Shared\ValueObject\Money;

/** The gateway reports a different amount than the invoice: never activate on that. */
final class PaymentAmountMismatch extends DomainRuleViolation
{
    public static function of(string $externalId, Money $expected, int $paid): self
    {
        return new self("Pembayaran {$externalId}: dibayar {$paid}, seharusnya {$expected->amount}.");
    }
}

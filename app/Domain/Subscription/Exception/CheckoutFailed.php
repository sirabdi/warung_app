<?php

namespace App\Domain\Subscription\Exception;

use App\Domain\Shared\Exception\DomainRuleViolation;

final class CheckoutFailed extends DomainRuleViolation
{
    public static function gatewayUnavailable(): self
    {
        return new self('Halaman pembayaran belum bisa dibuka. Coba lagi sebentar lagi.');
    }

    public function field(): string
    {
        return 'plan';
    }
}

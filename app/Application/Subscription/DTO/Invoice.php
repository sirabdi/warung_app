<?php

namespace App\Application\Subscription\DTO;

/** What the gateway hands back: its own id and the page where the customer pays. */
final readonly class Invoice
{
    public function __construct(
        public string $id,
        public string $checkoutUrl,
    ) {}
}

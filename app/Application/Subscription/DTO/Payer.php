<?php

namespace App\Application\Subscription\DTO;

/** Who pays, as shown on the gateway's invoice page. */
final readonly class Payer
{
    public function __construct(
        public string $name,
        public string $email,
        public string $storeName,
    ) {}
}

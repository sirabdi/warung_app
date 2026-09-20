<?php

namespace App\Application\Shared;

use DateTimeImmutable;

/** A source of time that can be frozen in tests. */
interface Clock
{
    public function now(): DateTimeImmutable;

    public function today(): DateTimeImmutable;
}

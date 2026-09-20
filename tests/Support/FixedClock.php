<?php

namespace Tests\Support;

use App\Application\Shared\Clock;
use DateTimeImmutable;

final class FixedClock implements Clock
{
    public function __construct(private readonly DateTimeImmutable $time) {}

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }

    public function today(): DateTimeImmutable
    {
        return $this->time->setTime(0, 0);
    }
}

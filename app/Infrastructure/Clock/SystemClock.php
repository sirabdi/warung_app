<?php

namespace App\Infrastructure\Clock;

use App\Application\Shared\Clock;
use DateTimeImmutable;

/** Uses the application clock (timezone from config/app.php, freezable in tests). */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return now()->toDateTimeImmutable();
    }

    public function today(): DateTimeImmutable
    {
        return today()->toDateTimeImmutable();
    }
}

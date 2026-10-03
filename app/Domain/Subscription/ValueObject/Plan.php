<?php

namespace App\Domain\Subscription\ValueObject;

use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;

/**
 * A subscription package. The backing value is its length in months, which is
 * also what the payments table stores.
 *
 * Prices are in whole rupiah. Changing one only affects new checkouts: every
 * payment keeps the amount it was created with.
 */
enum Plan: int
{
    case OneMonth = 1;
    case ThreeMonths = 3;
    case NineMonths = 9;
    case OneYear = 12;
    case TwoYears = 24;

    public function months(): int
    {
        return $this->value;
    }

    public function price(): Money
    {
        return Money::of(match ($this) {
            self::OneMonth => 35_000,
            self::ThreeMonths => 99_000,
            self::NineMonths => 279_000,
            self::OneYear => 349_000,
            self::TwoYears => 599_000,
        });
    }

    public function label(): string
    {
        return match ($this) {
            self::OneYear => '1 tahun',
            self::TwoYears => '2 tahun',
            default => "{$this->value} bulan",
        };
    }

    /**
     * Same day of the month, n months later. A day that does not exist there
     * is clamped: 31 January + 1 month = 28/29 February, not 3 March.
     */
    public function addTo(DateTimeImmutable $from): DateTimeImmutable
    {
        $month = (int) $from->format('n') - 1 + $this->months();
        $year = (int) $from->format('Y') + intdiv($month, 12);
        $month = $month % 12 + 1;

        $lastDay = (int) $from->setDate($year, $month, 1)->format('t');

        return $from->setDate($year, $month, min((int) $from->format('j'), $lastDay));
    }
}

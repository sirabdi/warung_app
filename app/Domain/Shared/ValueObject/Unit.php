<?php

namespace App\Domain\Shared\ValueObject;

/**
 * How a product is counted and sold.
 *
 * Quantities are always stored as whole numbers of the smallest step: pieces,
 * grams or millilitres. "1,52 kg" is 1520 — no floats anywhere, the same idea
 * as Money storing whole rupiah.
 */
enum Unit: string
{
    case Piece = 'pcs';
    case Kilogram = 'kg';
    case Liter = 'liter';

    /** Rounding step for the price of weighed goods, so change is easy to give. */
    private const MEASURED_PRICE_STEP = 100;

    /** Stored steps per displayed unit: 1 pcs, 1000 gram, 1000 ml. */
    public function scale(): int
    {
        return match ($this) {
            self::Piece => 1,
            self::Kilogram, self::Liter => 1000,
        };
    }

    /** Weighed or measured at the counter, so fractions are allowed. */
    public function isMeasured(): bool
    {
        return $this !== self::Piece;
    }

    /**
     * What the customer pays for $qty at $unitPrice (the price per pcs/kg/liter).
     * Pieces are exact; weighed goods are rounded to the nearest Rp 100.
     */
    public function charge(Money $unitPrice, int $qty): Money
    {
        if (! $this->isMeasured()) {
            return $unitPrice->times($qty);
        }

        $step = self::MEASURED_PRICE_STEP;

        return Money::of((int) round($unitPrice->amount * $qty / $this->scale() / $step) * $step);
    }

    /** Exact value of $qty at $unitPrice, rounded to the rupiah (used for cost). */
    public function value(Money $unitPrice, int $qty): Money
    {
        return Money::of((int) round($unitPrice->amount * $qty / $this->scale()));
    }

    /** One weighed line counts as one item; pieces count one by one. */
    public function itemCount(int $qty): int
    {
        return $this->isMeasured() ? 1 : $qty;
    }

    /** 1520 kg -> "1,52 kg"; pieces stay a bare number: 12 -> "12". */
    public function format(int $qty): string
    {
        if (! $this->isMeasured()) {
            return number_format($qty, 0, ',', '.');
        }

        $number = rtrim(rtrim(number_format($qty / $this->scale(), 3, ',', '.'), '0'), ',');

        return "{$number} {$this->value}";
    }
}

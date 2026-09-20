<?php

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;
use JsonSerializable;

/**
 * Whole rupiah (no cents) — the way a warung actually counts.
 * Stored as an integer so there are no floating point rounding errors.
 */
final readonly class Money implements JsonSerializable
{
    private function __construct(public int $amount) {}

    public static function of(int $amount): self
    {
        if ($amount < 0) {
            throw new InvalidValue('Harga tidak boleh minus.');
        }

        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /** Result of a calculation (e.g. gross profit) that is allowed to be negative. */
    public static function signed(int $amount): self
    {
        return new self($amount);
    }

    public function times(int $multiplier): self
    {
        return self::of($this->amount * $multiplier);
    }

    public function plus(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    /** May go negative: selling below cost price yields a negative profit. */
    public function minus(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function format(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function jsonSerialize(): int
    {
        return $this->amount;
    }
}

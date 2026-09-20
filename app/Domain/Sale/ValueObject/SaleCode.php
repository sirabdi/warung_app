<?php

namespace App\Domain\Sale\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;
use DateTimeImmutable;
use Stringable;

/**
 * Identifies one press of "Selesai" at the till: ymdHis + 4 random letters.
 * A single code can cover several product lines.
 */
final readonly class SaleCode implements Stringable
{
    private const LETTERS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function __construct(public string $value) {}

    public static function for(DateTimeImmutable $soldAt): self
    {
        $random = '';
        for ($i = 0; $i < 4; $i++) {
            $random .= self::LETTERS[random_int(0, strlen(self::LETTERS) - 1)];
        }

        return new self($soldAt->format('ymdHis').$random);
    }

    public static function of(string $value): self
    {
        if (trim($value) === '') {
            throw new InvalidValue('Kode penjualan kosong.');
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Domain\Shared\ValueObject\Unit;

/** SQL that differs per unit, built from the Unit enum so the two never drift apart. */
trait BuildsUnitSql
{
    /** CASE products.unit WHEN 'pcs' THEN … END. */
    private function perUnitSql(callable $expression): string
    {
        $cases = array_map(
            fn (Unit $unit) => "WHEN '{$unit->value}' THEN ".$expression($unit),
            Unit::cases(),
        );

        return 'CASE products.unit '.implode(' ', $cases).' END';
    }

    /** At or below $threshold display units: 5 pcs, or 5 kg = 5000 gram. */
    private function lowStockSql(int $threshold): string
    {
        return 'products.stock <= '.$this->perUnitSql(fn (Unit $unit) => $threshold * $unit->scale());
    }

    /** Emptiest first, compared in display units (1,4 kg before 2 pcs). */
    private function stockInDisplayUnitsSql(): string
    {
        return 'products.stock / '.$this->perUnitSql(fn (Unit $unit) => $unit->scale());
    }
}

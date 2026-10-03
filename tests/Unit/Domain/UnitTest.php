<?php

namespace Tests\Unit\Domain;

use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;
use PHPUnit\Framework\TestCase;

/** Pure domain test: no Laravel, no database. */
class UnitTest extends TestCase
{
    public function test_pieces_are_charged_exactly(): void
    {
        $this->assertSame(10350, Unit::Piece->charge(Money::of(3450), 3)->amount);
    }

    public function test_weighed_goods_are_rounded_to_the_nearest_100(): void
    {
        // 1,52 kg rice at Rp 14.000/kg = Rp 21.280 -> Rp 21.300
        $this->assertSame(21300, Unit::Kilogram->charge(Money::of(14000), 1520)->amount);
        // 0,25 liter oil at Rp 18.000/liter = Rp 4.500 exactly
        $this->assertSame(4500, Unit::Liter->charge(Money::of(18000), 250)->amount);
        // Rp 21.240 -> Rp 21.200
        $this->assertSame(21200, Unit::Kilogram->charge(Money::of(14000), 1517)->amount);
    }

    public function test_cost_is_not_rounded_to_the_hundred(): void
    {
        $this->assertSame(18240, Unit::Kilogram->value(Money::of(12000), 1520)->amount);
    }

    public function test_quantities_are_formatted_with_their_unit(): void
    {
        $this->assertSame('12', Unit::Piece->format(12));
        $this->assertSame('1,52 kg', Unit::Kilogram->format(1520));
        $this->assertSame('25 kg', Unit::Kilogram->format(25000));
        $this->assertSame('0,25 liter', Unit::Liter->format(250));
        $this->assertSame('1.250,5 kg', Unit::Kilogram->format(1250500));
    }

    public function test_a_weighed_line_counts_as_one_item(): void
    {
        $this->assertSame(5, Unit::Piece->itemCount(5));
        $this->assertSame(1, Unit::Kilogram->itemCount(1520));
    }
}

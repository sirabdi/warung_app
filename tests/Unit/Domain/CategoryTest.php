<?php

namespace Tests\Unit\Domain;

use App\Domain\Category\Entity\Category;
use App\Domain\Shared\Exception\InvalidValue;
use PHPUnit\Framework\TestCase;

/** Pure domain test: no Laravel, no database. */
class CategoryTest extends TestCase
{
    public function test_name_is_trimmed(): void
    {
        $this->assertSame('Minuman', Category::create('  Minuman ')->name());
    }

    public function test_blank_name_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);
        $this->expectExceptionMessage('Nama kategori wajib diisi.');

        Category::create('   ');
    }

    public function test_name_has_a_maximum_length(): void
    {
        $this->expectException(InvalidValue::class);

        Category::reconstitute(1, 'Rokok')->rename(str_repeat('a', 51));
    }
}

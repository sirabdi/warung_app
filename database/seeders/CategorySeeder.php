<?php

namespace Database\Seeders;

use App\Application\Category\DefaultCategories;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Default warung categories for the owner's store (new stores get them when
 * they register). Safe to run again: existing names are left alone, and
 * categories the owner added or renamed are never touched.
 */
class CategorySeeder extends Seeder
{
    use UsesOwnerStore;

    public const SEMBAKO = DefaultCategories::SEMBAKO;

    public const BUMBU = DefaultCategories::BUMBU;

    public const MINUMAN = DefaultCategories::MINUMAN;

    public const SNACK = DefaultCategories::SNACK;

    public const MANDI = DefaultCategories::MANDI;

    public const RUMAH_TANGGA = DefaultCategories::RUMAH_TANGGA;

    public const ROKOK = DefaultCategories::ROKOK;

    public const OBAT = DefaultCategories::OBAT;

    public const GAS = DefaultCategories::GAS;

    public const LAYANAN = DefaultCategories::LAYANAN;

    public const SIAP_SAJI = DefaultCategories::SIAP_SAJI;

    public const DEFAULTS = DefaultCategories::NAMES;

    public function run(): void
    {
        $this->useOwnerStore();

        foreach (self::DEFAULTS as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}

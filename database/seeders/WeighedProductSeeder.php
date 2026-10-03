<?php

namespace Database\Seeders;

use App\Domain\Shared\ValueObject\Unit;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\StockIn;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-off conversion of packaged products into goods weighed at the counter.
 *
 * "Beras 1kg" sold per piece becomes "Beras" sold per kg: 10 pcs of 1 kg is
 * 10 kg = 10000 gram. Past sales and stock-ins of the product are converted
 * the same way so old reports keep their meaning (prices were already per
 * kg/liter, and line totals do not change). Products that are no longer per
 * piece are skipped, so running it again does nothing.
 */
class WeighedProductSeeder extends Seeder
{
    /** Old packaged name => [new name, unit]. One old piece = one new unit. */
    public const CONVERSIONS = [
        'Beras 1kg' => ['Beras', Unit::Kilogram],
        'Minyak Goreng 1L' => ['Minyak Goreng', Unit::Liter],
    ];

    public function run(): void
    {
        foreach (self::CONVERSIONS as $oldName => [$newName, $unit]) {
            $product = Product::where('name', $oldName)->where('unit', Unit::Piece->value)->first();

            if (! $product) {
                continue;
            }

            if (Product::where('name', $newName)->exists()) {
                $this->command?->warn("Lewati {$oldName}: produk {$newName} sudah ada.");

                continue;
            }

            DB::transaction(function () use ($product, $newName, $unit) {
                $scale = $unit->scale();

                Transaction::where('product_id', $product->id)->update(['qty' => DB::raw("qty * {$scale}")]);
                StockIn::where('product_id', $product->id)->update(['qty' => DB::raw("qty * {$scale}")]);

                $product->update([
                    'name' => $newName,
                    'unit' => $unit->value,
                    'stock' => $product->stock * $scale,
                ]);
            });

            $this->command?->info("{$oldName} → {$newName} ({$unit->value})");
        }
    }
}

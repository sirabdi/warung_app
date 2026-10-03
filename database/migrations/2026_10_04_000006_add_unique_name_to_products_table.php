<?php

use App\Domain\Product\Entity\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A product name is unique within its store, like a category name. The app
 * already checks this; the index is the last guard against two saves at once.
 *
 * Existing names are stored the way new ones are (single spaces). Names that
 * then clash get " (2)", " (3)", … so the index can be built; the owner can
 * merge or rename them afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        $seen = [];

        foreach (DB::table('products')->orderBy('id')->get(['id', 'store_id', 'name']) as $row) {
            $name = Product::normalizeName($row->name);
            $base = $name;

            for ($n = 2; isset($seen[$row->store_id][Product::nameKey($name)]); $n++) {
                $name = "{$base} ({$n})";
            }
            $seen[$row->store_id][Product::nameKey($name)] = true;

            if ($name !== $row->name) {
                DB::table('products')->where('id', $row->id)->update(['name' => $name]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['store_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'name']);
        });
    }
};

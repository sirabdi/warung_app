<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every row now belongs to a store. Data from before stores existed becomes the
 * first store, with a subscription that does not run out: it belongs to the
 * owner of the app, not to a paying customer.
 *
 * Category names are unique per store instead of across the whole app.
 */
return new class extends Migration
{
    private const TABLES = ['users', 'categories', 'products', 'transactions', 'stock_ins'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $hasData = collect(self::TABLES)->contains(fn ($name) => DB::table($name)->exists());
        if ($hasData) {
            $storeId = DB::table('stores')->insertGetId([
                'name' => env('WARUNG_STORE_NAME', 'Warung'),
                'phone' => '-',
                'address' => '-',
                'subscription_ends_at' => '2099-12-31 23:59:59',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (self::TABLES as $name) {
                DB::table($name)->update(['store_id' => $storeId]);
            }
        }

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('store_id')->nullable(false)->change();
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['store_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'name']);
            $table->unique('name');
        });

        foreach (array_reverse(self::TABLES) as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('store_id');
            });
        }
    }
};

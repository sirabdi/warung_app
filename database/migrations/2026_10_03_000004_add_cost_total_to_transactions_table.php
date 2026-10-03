<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gross profit used to be total - cost_price * qty, which is wrong once qty is
 * in grams. The cost of each line is now stored like its total. Every existing
 * row is in pieces, so the old formula fills it in exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_total')->default(0)->after('cost_price');
        });

        DB::table('transactions')->update(['cost_total' => DB::raw('cost_price * qty')]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('cost_total');
        });
    }
};

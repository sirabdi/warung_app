<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pcs, kg or liter (App\Domain\Shared\ValueObject\Unit). Existing products are
 * all pieces, so their numbers stay exactly as they are. For kg/liter, stock and
 * every qty column hold grams/ml.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit', 10)->default('pcs')->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};

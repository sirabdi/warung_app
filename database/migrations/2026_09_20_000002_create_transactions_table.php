<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row = one product within a single checkout.
        // Rows of the same checkout are grouped by the `code` column.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->index();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price'); // sell price at the time of the sale
            $table->unsignedInteger('cost_price')->default(0); // to compute gross profit
            $table->unsignedBigInteger('total');
            $table->dateTime('sold_at')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

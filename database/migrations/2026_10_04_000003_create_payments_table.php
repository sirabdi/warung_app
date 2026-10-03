<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per checkout; see App\Domain\Subscription\Entity\Payment. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('plan'); // months
            $table->unsignedInteger('amount');
            $table->string('status', 10)->index();
            $table->string('external_id', 40)->unique();
            $table->string('gateway', 20);
            $table->string('invoice_id', 100)->nullable();
            $table->text('checkout_url')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('period_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

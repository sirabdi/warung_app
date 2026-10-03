<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per customer (one store, one login). subscription_ends_at is null
 * until the first payment; see App\Domain\Subscription\Entity\Subscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('address', 255);
            $table->dateTime('subscription_ends_at')->nullable()->index();
            // "{days}:{end date}" of the last reminder email, so it is sent once.
            $table->string('last_reminder', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};

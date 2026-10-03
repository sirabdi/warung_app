<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\Store;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Tenancy\CurrentStore;
use RuntimeException;

/**
 * Seeders write into one store. Unless one was chosen already (by
 * DatabaseSeeder, or a logged-in test user), that is the store of the owner
 * account from WARUNG_USER_EMAIL.
 */
trait UsesOwnerStore
{
    protected function useOwnerStore(): int
    {
        $current = app(CurrentStore::class);

        if ($current->id() !== null) {
            return $current->id();
        }

        $storeId = User::where('email', env('WARUNG_USER_EMAIL', 'warung@example.com'))->value('store_id')
            ?? Store::orderBy('id')->value('id')
            ?? throw new RuntimeException('Belum ada toko. Jalankan dulu: php artisan db:seed');

        $current->set($storeId);

        return $storeId;
    }
}

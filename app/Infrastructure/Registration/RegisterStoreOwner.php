<?php

namespace App\Infrastructure\Registration;

use App\Application\Category\DefaultCategories;
use App\Application\Category\UseCase\AddCategory;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Tenancy\CurrentStore;
use Illuminate\Support\Facades\DB;

/**
 * A new customer: their store (not paid yet), their login and the default
 * categories, all or nothing.
 */
final readonly class RegisterStoreOwner
{
    public function __construct(
        private CurrentStore $currentStore,
        private AddCategory $addCategory,
    ) {}

    /** @param array{name: string, phone: string, store_name: string, store_address: string, email: string, password: string} $data */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $store = Store::create([
                'name' => $data['store_name'],
                'phone' => $data['phone'],
                'address' => $data['store_address'],
            ]);

            $user = new User([
                'store_id' => $store->id,
                'name' => $data['name'],
                'email' => EmailOtp::normalize($data['email']),
                'password' => $data['password'],
            ]);
            // The address was proven with the OTP before this point.
            $user->email_verified_at = now();
            $user->save();

            $this->currentStore->as($store->id, function () {
                foreach (DefaultCategories::NAMES as $name) {
                    $this->addCategory->execute($name);
                }
            });

            return $user;
        });
    }
}

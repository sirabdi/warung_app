<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\Store;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Tenancy\CurrentStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('WARUNG_USER_EMAIL', 'warung@example.com');

        // The app owner's own store never runs out; customers pay for theirs.
        $storeId = User::where('email', $email)->value('store_id')
            ?? Store::create([
                'name' => env('WARUNG_STORE_NAME', 'Warung'),
                'phone' => '-',
                'address' => '-',
                'subscription_ends_at' => '2099-12-31 23:59:59',
            ])->id;

        User::updateOrCreate(
            ['email' => $email],
            [
                'store_id' => $storeId,
                'name' => env('WARUNG_USER_NAME', 'Pemilik Warung'),
                'password' => Hash::make(env('WARUNG_USER_PASSWORD', 'rahasia123')),
                'email_verified_at' => now(),
            ],
        );

        // A ready-made /admin login for development; elsewhere use `php artisan admin:create`.
        if (app()->environment('local')) {
            User::updateOrCreate(
                ['email' => env('WARUNG_ADMIN_EMAIL', 'admin@example.com')],
                [
                    'store_id' => null,
                    'name' => 'Admin',
                    'password' => Hash::make(env('WARUNG_ADMIN_PASSWORD', 'rahasia123')),
                    'email_verified_at' => now(),
                ],
            )->forceFill(['is_admin' => true])->save();
        }

        app(CurrentStore::class)->set($storeId);

        if (app()->environment('local') && Product::count() === 0) {
            $samples = [
                ['name' => 'Indomie Goreng', 'cost_price' => 2800, 'sell_price' => 3500, 'stock' => 40],
                ['name' => 'Aqua Botol 600ml', 'cost_price' => 3000, 'sell_price' => 4000, 'stock' => 24],
                ['name' => 'Teh Pucuk 350ml', 'cost_price' => 3500, 'sell_price' => 5000, 'stock' => 18],
                ['name' => 'Kopi Kapal Api Sachet', 'cost_price' => 1200, 'sell_price' => 2000, 'stock' => 50],
                ['name' => 'Telur (butir)', 'cost_price' => 2200, 'sell_price' => 2800, 'stock' => 60],
                ['name' => 'Beras 1kg', 'cost_price' => 12000, 'sell_price' => 14000, 'stock' => 10],
                ['name' => 'Gula 1kg', 'cost_price' => 15000, 'sell_price' => 17000, 'stock' => 4],
                ['name' => 'Minyak Goreng 1L', 'cost_price' => 16000, 'sell_price' => 18500, 'stock' => 3],
                ['name' => 'Rokok Sampoerna', 'cost_price' => 28000, 'sell_price' => 31000, 'stock' => 12],
                ['name' => 'Sabun Lifebuoy', 'cost_price' => 3500, 'sell_price' => 4500, 'stock' => 8],
            ];

            foreach ($samples as $sample) {
                Product::create($sample);
            }
        }

        // Default categories in every environment; sample products get theirs.
        $this->call(ProductCategorySeeder::class);

        // Rice and bulk oil are weighed at the counter, not sold per bag.
        $this->call(WeighedProductSeeder::class);
    }
}

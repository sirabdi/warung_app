<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Puts the products that existed before categories into a default category.
 * Only products without a category are touched, so running it again never
 * undoes a choice made in the app.
 */
class ProductCategorySeeder extends Seeder
{
    use UsesOwnerStore;

    /** Product name => default category. */
    public const MAP = [
        'Indomie Goreng' => CategorySeeder::SEMBAKO,
        'Telur (butir)' => CategorySeeder::SEMBAKO,
        'Beras 1kg' => CategorySeeder::SEMBAKO,
        'Beras' => CategorySeeder::SEMBAKO,
        'Gula 1kg' => CategorySeeder::SEMBAKO,
        'Minyak Goreng 1L' => CategorySeeder::SEMBAKO,
        'Minyak Goreng' => CategorySeeder::SEMBAKO,
        'Aqua Botol 600ml' => CategorySeeder::MINUMAN,
        'Teh Pucuk 350ml' => CategorySeeder::MINUMAN,
        'Kopi Kapal Api Sachet' => CategorySeeder::MINUMAN,
        'Rokok Sampoerna' => CategorySeeder::ROKOK,
        'Sabun Lifebuoy' => CategorySeeder::MANDI,
    ];

    public function run(): void
    {
        $this->useOwnerStore();
        $this->call(CategorySeeder::class);

        $categoryIds = Category::pluck('id', 'name');

        foreach (self::MAP as $product => $category) {
            Product::where('name', $product)
                ->whereNull('category_id')
                ->update(['category_id' => $categoryIds[$category]]);
        }

        $left = Product::whereNull('category_id')->pluck('name');
        if ($left->isNotEmpty() && $this->command) {
            $this->command->warn('Belum berkategori, atur lewat menu Produk: '.$left->implode(', '));
        }
    }
}

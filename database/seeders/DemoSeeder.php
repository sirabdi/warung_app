<?php

namespace Database\Seeders;

use App\Application\Cashier\DTO\Cart;
use App\Application\Cashier\UseCase\RecordSale;
use App\Application\Product\DTO\ProductData;
use App\Application\Product\UseCase\AddProduct;
use App\Application\Shared\Clock;
use App\Application\Shared\TransactionManager;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Sale\Repository\SaleRepository;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\Shared\ValueObject\Unit;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo data for trying the app locally: a full warung shelf and a day of sales.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Everything goes through the real use cases (AddProduct, RecordSale), so stock
 * drops, costs and the Rp 100 rounding of weighed goods are exactly what the
 * till would produce. Products that already exist are skipped, and sales are
 * only added when today has none yet, so running it twice is harmless.
 */
class DemoSeeder extends Seeder
{
    /**
     * [name, category, unit, sell price, cost price, stock in display units].
     * Prices of kg/liter goods are per kg/liter. A few start low on purpose so
     * the "stok menipis" list has something to show.
     */
    private const PRODUCTS = [
        // Sembako
        ['Indomie Soto', CategorySeeder::SEMBAKO, 'pcs', 3500, 2800, 40],
        ['Mie Sedaap Goreng', CategorySeeder::SEMBAKO, 'pcs', 3500, 2700, 36],
        ['Sarimi Isi 2', CategorySeeder::SEMBAKO, 'pcs', 4000, 3200, 24],
        ['Gula Pasir ½ kg', CategorySeeder::SEMBAKO, 'pcs', 9000, 7800, 12],
        ['Gula Pasir ¼ kg', CategorySeeder::SEMBAKO, 'pcs', 4500, 3900, 3],
        ['Tepung Terigu Segitiga Biru 1kg', CategorySeeder::SEMBAKO, 'pcs', 13000, 11000, 8],
        ['Garam Dapur 250g', CategorySeeder::SEMBAKO, 'pcs', 3000, 2000, 15],
        ['Susu Kental Manis Frisian Flag', CategorySeeder::SEMBAKO, 'pcs', 12000, 10500, 10],
        ['Mi Telor Cap 3 Ayam', CategorySeeder::SEMBAKO, 'pcs', 6000, 4800, 4],

        // Bumbu dan bahan masak (bawang & cabai ditimbang)
        ['Bawang Merah', CategorySeeder::BUMBU, 'kg', 40000, 34000, 3],
        ['Bawang Putih', CategorySeeder::BUMBU, 'kg', 36000, 30000, 6],
        ['Cabai Rawit', CategorySeeder::BUMBU, 'kg', 60000, 50000, 1.5],
        ['Kecap Manis Bango 220ml', CategorySeeder::BUMBU, 'pcs', 11000, 9500, 9],
        ['Saus Sambal ABC 135ml', CategorySeeder::BUMBU, 'pcs', 8000, 6500, 7],
        ['Royco Ayam Sachet', CategorySeeder::BUMBU, 'pcs', 1000, 700, 60],
        ['Masako Sapi Sachet', CategorySeeder::BUMBU, 'pcs', 1000, 700, 48],
        ['Merica Ladaku Sachet', CategorySeeder::BUMBU, 'pcs', 1000, 700, 40],
        ['Santan Kara 65ml', CategorySeeder::BUMBU, 'pcs', 3500, 2800, 20],
        ['Ketumbar Bubuk Desaku', CategorySeeder::BUMBU, 'pcs', 1000, 700, 30],

        // Minuman
        ['Aqua Galon Isi Ulang', CategorySeeder::MINUMAN, 'pcs', 6000, 4000, 10],
        ['Teh Botol Sosro 450ml', CategorySeeder::MINUMAN, 'pcs', 5000, 3800, 24],
        ['Coca-Cola 390ml', CategorySeeder::MINUMAN, 'pcs', 6000, 4500, 12],
        ['Fanta Stroberi 390ml', CategorySeeder::MINUMAN, 'pcs', 6000, 4500, 2],
        ['Pocari Sweat 500ml', CategorySeeder::MINUMAN, 'pcs', 8000, 6500, 10],
        ['Good Day Cappuccino Sachet', CategorySeeder::MINUMAN, 'pcs', 2000, 1400, 40],
        ['Teh Celup Sariwangi isi 25', CategorySeeder::MINUMAN, 'pcs', 7000, 5800, 8],
        ['Susu Ultra Coklat 250ml', CategorySeeder::MINUMAN, 'pcs', 6500, 5200, 18],
        ['Le Minerale 600ml', CategorySeeder::MINUMAN, 'pcs', 4000, 2900, 24],
        ['Extra Joss Sachet', CategorySeeder::MINUMAN, 'pcs', 2000, 1400, 30],

        // Makanan ringan
        ['Chitato Sapi Panggang 68g', CategorySeeder::SNACK, 'pcs', 11000, 9000, 8],
        ['Taro Net 70g', CategorySeeder::SNACK, 'pcs', 9000, 7300, 6],
        ['Qtela Singkong 60g', CategorySeeder::SNACK, 'pcs', 7000, 5600, 10],
        ['Beng-Beng', CategorySeeder::SNACK, 'pcs', 2500, 1900, 30],
        ['SilverQueen Chunky 30g', CategorySeeder::SNACK, 'pcs', 15000, 12500, 5],
        ['Oreo 133g', CategorySeeder::SNACK, 'pcs', 9500, 7800, 7],
        ['Roma Kelapa 300g', CategorySeeder::SNACK, 'pcs', 11000, 9000, 6],
        ['Wafer Tango 130g', CategorySeeder::SNACK, 'pcs', 8000, 6500, 1],
        ['Permen Kopiko', CategorySeeder::SNACK, 'pcs', 500, 300, 100],
        ['Choki-Choki', CategorySeeder::SNACK, 'pcs', 1000, 700, 48],

        // Perlengkapan mandi dan kebersihan diri
        ['Shampo Sunsilk Sachet', CategorySeeder::MANDI, 'pcs', 1000, 700, 48],
        ['Pasta Gigi Pepsodent 190g', CategorySeeder::MANDI, 'pcs', 13000, 11000, 6],
        ['Sikat Gigi Formula', CategorySeeder::MANDI, 'pcs', 6000, 4500, 10],
        ['Sabun Cair Lifebuoy Refill 250ml', CategorySeeder::MANDI, 'pcs', 15000, 12500, 4],
        ['Pembalut Charm isi 8', CategorySeeder::MANDI, 'pcs', 9000, 7500, 8],

        // Kebutuhan rumah tangga
        ['Rinso Anti Noda 770g', CategorySeeder::RUMAH_TANGGA, 'pcs', 25000, 21500, 6],
        ['Sunlight Jeruk Nipis 755ml', CategorySeeder::RUMAH_TANGGA, 'pcs', 17000, 14500, 5],
        ['So Klin Pewangi Sachet', CategorySeeder::RUMAH_TANGGA, 'pcs', 1000, 700, 40],
        ['Baygon Semprot 600ml', CategorySeeder::RUMAH_TANGGA, 'pcs', 38000, 33000, 3],
        ['Obat Nyamuk Bakar Baygon', CategorySeeder::RUMAH_TANGGA, 'pcs', 6000, 4800, 12],
        ['Tisu Paseo 250 Lembar', CategorySeeder::RUMAH_TANGGA, 'pcs', 12000, 10000, 8],
        ['Korek Api Gas', CategorySeeder::RUMAH_TANGGA, 'pcs', 3000, 2000, 20],
        ['Lilin Batang', CategorySeeder::RUMAH_TANGGA, 'pcs', 1000, 600, 24],

        // Rokok
        ['Gudang Garam Filter 12', CategorySeeder::ROKOK, 'pcs', 25000, 22500, 15],
        ['Djarum Super 12', CategorySeeder::ROKOK, 'pcs', 24000, 21500, 12],
        ['Surya 16', CategorySeeder::ROKOK, 'pcs', 33000, 30000, 10],
        ['Sampoerna Mild 16', CategorySeeder::ROKOK, 'pcs', 32000, 29500, 2],
        ['Marlboro Merah 20', CategorySeeder::ROKOK, 'pcs', 40000, 37000, 6],

        // Obat-obatan ringan
        ['Bodrex Strip', CategorySeeder::OBAT, 'pcs', 3000, 2200, 20],
        ['Promag Strip', CategorySeeder::OBAT, 'pcs', 9000, 7500, 8],
        ['Tolak Angin Cair', CategorySeeder::OBAT, 'pcs', 4000, 3200, 24],
        ['Antangin JRG', CategorySeeder::OBAT, 'pcs', 4000, 3200, 20],
        ['Minyak Kayu Putih Cap Lang 60ml', CategorySeeder::OBAT, 'pcs', 22000, 19000, 4],
        ['Komix Sachet', CategorySeeder::OBAT, 'pcs', 2000, 1500, 30],
        ['Hansaplast isi 10', CategorySeeder::OBAT, 'pcs', 5000, 3800, 10],

        // Gas dan bahan bakar (eceran ditakar per liter)
        ['Gas LPG 3kg (isi ulang)', CategorySeeder::GAS, 'pcs', 22000, 19000, 8],
        ['Pertalite Eceran', CategorySeeder::GAS, 'liter', 12000, 10500, 20],
        ['Minyak Tanah', CategorySeeder::GAS, 'liter', 15000, 12500, 4],

        // Layanan tambahan
        ['Pulsa 10.000', CategorySeeder::LAYANAN, 'pcs', 12000, 10800, 100],
        ['Pulsa 25.000', CategorySeeder::LAYANAN, 'pcs', 27000, 25500, 100],
        ['Token Listrik 20.000', CategorySeeder::LAYANAN, 'pcs', 22500, 20500, 100],
        ['Token Listrik 50.000', CategorySeeder::LAYANAN, 'pcs', 52500, 50500, 100],

        // Makanan dan minuman siap saji
        ['Nasi Uduk Bungkus', CategorySeeder::SIAP_SAJI, 'pcs', 7000, 5000, 12],
        ['Gorengan Bakwan', CategorySeeder::SIAP_SAJI, 'pcs', 1000, 600, 30],
        ['Es Teh Manis', CategorySeeder::SIAP_SAJI, 'pcs', 3000, 1200, 50],
        ['Kopi Hitam Seduh', CategorySeeder::SIAP_SAJI, 'pcs', 3000, 1000, 50],
        ['Pop Mie Ayam', CategorySeeder::SIAP_SAJI, 'pcs', 6000, 4800, 12],
        ['Lontong Sayur', CategorySeeder::SIAP_SAJI, 'pcs', 8000, 5500, 6],
    ];

    /**
     * Today's checkouts: product name => qty in display units (1.5 = 1,5 kg).
     * Written out instead of random so the report always tells the same story.
     */
    private const SALES = [
        ['Kopi Hitam Seduh' => 2, 'Gorengan Bakwan' => 4, 'Nasi Uduk Bungkus' => 1],
        ['Beras' => 2, 'Telur (butir)' => 6, 'Minyak Goreng' => 0.5],
        ['Gudang Garam Filter 12' => 1, 'Korek Api Gas' => 1],
        ['Indomie Goreng' => 5, 'Indomie Soto' => 3, 'Bawang Merah' => 0.25],
        ['Token Listrik 50.000' => 1],
        ['Aqua Botol 600ml' => 2, 'Chitato Sapi Panggang 68g' => 1, 'Beng-Beng' => 3],
        ['Pertalite Eceran' => 1.5],
        ['Rinso Anti Noda 770g' => 1, 'Sunlight Jeruk Nipis 755ml' => 1, 'So Klin Pewangi Sachet' => 4],
        ['Beras' => 1.52, 'Cabai Rawit' => 0.1, 'Bawang Putih' => 0.25, 'Royco Ayam Sachet' => 3],
        ['Djarum Super 12' => 1, 'Es Teh Manis' => 2],
        ['Pulsa 25.000' => 1, 'Teh Botol Sosro 450ml' => 2],
        ['Tolak Angin Cair' => 2, 'Bodrex Strip' => 1, 'Minyak Kayu Putih Cap Lang 60ml' => 1],
        ['Pop Mie Ayam' => 2, 'Le Minerale 600ml' => 2, 'Kopi Kapal Api Sachet' => 5],
        ['Gas LPG 3kg (isi ulang)' => 1, 'Minyak Goreng' => 1],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder hanya untuk lokal: datanya palsu.');
        }

        $this->call(CategorySeeder::class);

        $this->seedProducts();
        $this->seedTodaysSales();
    }

    private function seedProducts(): void
    {
        $categoryIds = Category::pluck('id', 'name');
        $addProduct = app(AddProduct::class);
        $added = 0;

        foreach (self::PRODUCTS as [$name, $category, $unit, $sellPrice, $costPrice, $stock]) {
            if (Product::where('name', $name)->exists()) {
                continue;
            }

            $unit = Unit::from($unit);

            $addProduct->execute(new ProductData(
                $name,
                $categoryIds[$category],
                Money::of($sellPrice),
                Money::of($costPrice),
                (int) round($stock * $unit->scale()),
                $unit,
            ));
            $added++;
        }

        $this->command?->info("{$added} produk demo ditambahkan (total ".Product::count().').');
    }

    private function seedTodaysSales(): void
    {
        if (Transaction::whereDate('sold_at', today())->exists()) {
            $this->command?->warn('Sudah ada penjualan hari ini, penjualan demo dilewati.');

            return;
        }

        $clock = new class implements Clock
        {
            public DateTimeImmutable $time;

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }

            public function today(): DateTimeImmutable
            {
                return $this->time->setTime(0, 0);
            }
        };

        $recordSale = new RecordSale(
            app(ProductRepository::class),
            app(SaleRepository::class),
            app(TransactionManager::class),
            $clock,
        );

        // Spread from 07:00 until now; before 08:00 squeeze into the last hour.
        $now = new DateTimeImmutable;
        $start = max($now->setTime(7, 0)->getTimestamp(), $now->getTimestamp() - 3600 * 7);
        $step = intdiv($now->getTimestamp() - 60 - $start, count(self::SALES));

        $recorded = 0;
        foreach (self::SALES as $i => $lines) {
            $rows = [];
            foreach ($lines as $name => $qty) {
                $product = Product::firstWhere('name', $name);
                if (! $product) {
                    continue;
                }

                $rows[] = [
                    'product_id' => $product->id,
                    'qty' => (int) round($qty * Unit::from($product->unit)->scale()),
                ];
            }

            if ($rows === []) {
                continue;
            }

            $clock->time = (new DateTimeImmutable)->setTimestamp($start + $step * $i + random_int(0, max(0, $step - 60)));
            $recordSale->execute(Cart::fromArray($rows));
            $recorded++;
        }

        $this->command?->info("{$recorded} penjualan demo hari ini dicatat.");
    }
}

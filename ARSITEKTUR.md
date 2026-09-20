# Arsitektur — DDD berlapis

Aplikasi ini dibagi jadi empat lapisan. Aturannya satu: **panah ketergantungan
selalu mengarah ke dalam**. Domain tidak tahu apa pun tentang Laravel, Eloquent,
atau HTTP.

Seluruh nama di dalam kode (kelas, method, variabel, kolom database, rute, props)
memakai bahasa Inggris. Semua teks yang dibaca pemilik warung — label tombol,
pesan sukses, pesan error — tetap bahasa Indonesia.

```
Presentation  →  Application  →  Domain
      ↓               ↓            ↑
         Infrastructure ───────────┘  (mengimplementasikan interface milik Domain/Application)
```

| Lapisan | Folder | Isi | Boleh bergantung pada |
| --- | --- | --- | --- |
| Domain | `app/Domain` | Entity, value object, aturan bisnis, interface repository | PHP murni saja |
| Application | `app/Application` | Use case, DTO, interface read model, port teknis | Domain |
| Infrastructure | `app/Infrastructure` | Eloquent, query builder, jam sistem, binding container | Domain + Application + Laravel |
| Presentation | `app/Presentation` | Controller, FormRequest, middleware Inertia | Application (+ Domain untuk exception) |

## Peta folder

```
app/
├── Domain/
│   ├── Shared/          Money (value object), DomainRuleViolation
│   ├── Product/         Entity Product, ProductRepository, InsufficientStock, …
│   ├── Sale/            Entity Sale + SaleItem, SaleCode, EmptyCart
│   └── Inventory/       Entity StockIn
├── Application/
│   ├── Shared/          Clock, TransactionManager (port teknis)
│   ├── Cashier/         RecordSale, Cart, ProductsForCashier
│   ├── Product/         AddProduct, UpdateProduct, ProductData, ProductList
│   ├── Inventory/       RecordStockIn, StockInHistory
│   └── Report/          DailyReportQuery + DTO laporan
├── Infrastructure/
│   ├── Persistence/Eloquent/
│   │   ├── Models/      Product, Transaction, StockIn, User (tanpa aturan bisnis)
│   │   ├── Mapper/      ProductMapper: baris tabel ⇄ entity
│   │   ├── Repository/  Eloquent*Repository
│   │   └── Query/       read model untuk tiap halaman
│   ├── Clock/           SystemClock
│   └── Provider/        DomainServiceProvider (semua binding)
└── Presentation/Http/   Controllers, Requests, Middleware
```

Model Eloquent dan entity domain sama-sama bernama `Product`/`StockIn`. Di berkas
yang memakai keduanya, model Eloquent diberi alias `ProductModel`/`StockInModel`.

## Alur satu transaksi jual

```
useRecordSale().mutate(items)    TanStack Query, dari halaman Cashier
  → POST /api/sales              fetch + cookie sesi + X-XSRF-TOKEN
  → RecordSaleRequest            validasi bentuk input, bikin DTO Cart
  → RecordSale (use case)        buka transaksi DB, kunci baris produk
      → Sale::sell()             salin harga, kurangi stok produk (aturan domain)
      → ProductRepository        simpan stok baru
      → SaleRepository           simpan baris transactions
  → CashierApiController         JSON { message: "Terjual Rp 8.000", total, … }
  → onSuccess                    toast + invalidateQueries → daftar produk segar
```

Kalau stok kurang, `Product::reduceStock()` melempar `InsufficientStock`. Exception
itu dipetakan jadi error validasi di `bootstrap/app.php`, jadi domain tidak pernah
menyentuh HTTP dan pesan tetap muncul rapi di form.

## Perintah vs query (CQRS ringan)

- **Menulis** lewat repository + aggregate, supaya aturan bisnis selalu jalan.
- **Membaca** lewat interface `*Query`/`*List` yang diimplementasikan langsung
  dengan query builder. Laporan dan daftar produk tidak perlu memuat aggregate
  hanya untuk ditampilkan; tidak ada aturan bisnis yang perlu dijaga di jalur baca.

## Frontend: Inertia + TanStack Query

Inertia menangani routing halaman, sesi, dan CSRF. Begitu halaman tampil,
**semua data dipegang TanStack Query**:

- Props Inertia dipakai sebagai `initialData`, jadi halaman langsung terisi
  tanpa spinner dan tanpa request kedua saat dibuka.
- Setelahnya data disegarkan sendiri (`staleTime` 30 detik, refetch saat tab
  kembali aktif atau koneksi pulih).
- Semua perubahan lewat `useMutation` ke API JSON, lalu `invalidateQueries`
  menyegarkan daftar yang terpengaruh — stok di halaman kasir ikut turun
  tanpa reload.

```
resources/js/
├── api/client.js    fetch + XSRF + ApiError (punya fieldErrors)
├── api/hooks.js     useCashierProducts, useProducts, useStockInHistory,
│                    useReport, useRecordSale, useRecordStockIn, useSaveProduct
├── toast.js         pesan sukses dari mutation (dulu dari flash Inertia)
└── Pages/           Cashier, Products, StockIn, Report, Login
```

Error validasi 422 dari Laravel dibungkus jadi `ApiError.fieldErrors`, sehingga
pesan domain (`Stok Beras tinggal 1.`) muncul persis di bawah kolom terkait.

## Rute

| Halaman (Inertia) | Controller | Komponen React |
| --- | --- | --- |
| `GET /` | CashierController@index | `Cashier` |
| `GET /products` | ProductController@index | `Products` |
| `GET /stock-in` | StockInController@index | `StockIn` |
| `GET /report` | ReportController@index | `Report` |

| API JSON (TanStack Query) | Controller |
| --- | --- |
| `GET /api/cashier/products` | Api\CashierApiController@index |
| `POST /api/sales` | Api\CashierApiController@store |
| `GET /api/products` | Api\ProductApiController@index |
| `POST /api/products`, `PUT /api/products/{product}` | Api\ProductApiController |
| `GET /api/stock-in/history`, `POST /api/stock-in` | Api\StockInApiController |
| `GET /api/report?date=` | Api\ReportApiController@index |

API sengaja berada di grup `web`, bukan `routes/api.php`: sesi dan token CSRF-nya
sama dengan halaman, jadi tidak perlu Sanctum atau token terpisah. Props dan
payload JSON memakai nama kolom Inggris (`name`, `sell_price`, `stock`,
`lowStockThreshold`, `bestSellers`, …).

## Menambah fitur baru

1. Aturan bisnisnya ditaruh di entity `app/Domain/...` — tulis tes unitnya dulu.
2. Alurnya jadi satu use case di `app/Application/<Konteks>/UseCase`.
3. Kalau butuh data baru, tambah method di interface repository/query.
4. Implementasi Eloquent-nya di `app/Infrastructure`, lalu daftarkan di
   `DomainServiceProvider`.
5. Controller tipis di `app/Presentation/Http` — hanya terjemahkan request →
   use case → response Inertia.

Catatan: fitur baru tetap harus lolos daftar scope di [CATATAN.md](CATATAN.md).
Arsitektur ini mempermudah penambahan fitur, bukan izin untuk mengerjakan backlog.

## Pengujian

| Jenis | Folder | Sifat |
| --- | --- | --- |
| Unit domain | `tests/Unit/Domain` | PHPUnit murni, tanpa Laravel & tanpa database |
| Unit use case | `tests/Unit/Application` | Repository palsu di memori (`tests/Support`) |
| Feature | `tests/Feature` | HTTP sungguhan (halaman Inertia + API JSON) lewat sqlite in-memory |

Tes domain dan use case jalan tanpa database — itu keuntungan utama pemisahan ini.

## Batasan yang disadari

- Repository memakai Eloquent model sebagai jembatan, bukan ORM terpisah. Untuk
  skala warung ini cukup; kalau nanti perlu, tinggal ganti implementasi di
  `Infrastructure` tanpa menyentuh domain.
- `users` masih dipakai apa adanya dari Laravel (`Authenticatable`) karena
  autentikasi bukan bagian dari domain warung.
- Tidak ada domain event / event sourcing. Belum ada kebutuhannya.
- Belum ada optimistic update di kasir: transaksi menunggu balasan server dulu.
  Untuk warung dengan satu kasir, kepastian stok lebih penting daripada
  hemat 200 ms. Kalau nanti perlu, tempatnya di `useRecordSale`.

---

# Lampiran: setup awal dari nol

Panduan menyiapkan project baru Laravel + Inertia + React + struktur DDD sampai
halaman pertama tampil. Hanya setup awal — fitur dibangun setelahnya mengikuti
bagian [Menambah fitur baru](#menambah-fitur-baru).

Prasyarat: PHP 8.2+, Composer 2, Node 20+, dan MySQL 8 (atau sqlite kalau hanya
ingin mencoba cepat).

## 1. Project Laravel + Inertia

```bash
composer create-project laravel/laravel nama-project
cd nama-project

composer require inertiajs/inertia-laravel:^3.3
php artisan inertia:middleware        # bikin app/Http/Middleware/HandleInertiaRequests.php
```

## 2. Dependensi frontend

```bash
npm install @inertiajs/react react react-dom @tanstack/react-query @vitejs/plugin-react
npm install -D @tailwindcss/vite tailwindcss
```

Catatan versi: `@vitejs/plugin-react` v6 menuntut Vite 8, sedangkan Laravel 12
masih membawa Vite 7 — pakai `@vitejs/plugin-react@^5` kalau npm menolak
dengan `ERESOLVE`.

## 3. `vite.config.js`

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({ input: 'resources/js/app.jsx', refresh: true }),
        react(),
        tailwindcss(),
    ],
});
```

## 4. CSS (Tailwind v4)

`resources/css/app.css` — v4 tidak lagi pakai `tailwind.config.js`; sumber kelas
ditulis dengan `@source`, dan kelas buatan sendiri dengan `@utility` (bukan
`@apply` di kelas biasa, karena `@apply` tidak bisa memanggil kelas custom).

```css
@import 'tailwindcss';

@source '../**/*.blade.php';
@source '../**/*.jsx';

@utility input {
    @apply w-full rounded-lg border border-stone-300 px-3 py-2.5;
}
```

## 5. Root view

`resources/views/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name') }}</title>
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
```

Baris `@vite` kedua membuat chunk halaman yang sedang dibuka ikut dimuat lebih
awal, jadi tidak ada kedipan saat berpindah halaman. **Jangan** memasukkan
`resources/css/app.css` ke daftar `@vite` kalau CSS itu sudah di-`import` dari
`app.jsx` — Vite tidak membuat entri manifest untuknya dan setiap halaman akan
gagal dengan `Unable to locate file in Vite manifest`.

## 6. Entry point React

`resources/js/app.jsx`:

```jsx
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: true } },
});

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup: ({ el, App, props }) =>
        createRoot(el).render(
            <QueryClientProvider client={queryClient}>
                <App {...props} />
            </QueryClientProvider>,
        ),
});
```

## 7. Kerangka folder DDD

Composer sudah memetakan `App\` ke `app/`, jadi **tidak ada yang perlu diubah di
`composer.json`** — cukup bikin foldernya.

```bash
mkdir -p app/Domain/{Shared/{Entity,ValueObject,Exception},Product/{Entity,Repository,Exception}}
mkdir -p app/Application/{Shared,Product/{UseCase,DTO,Query}}
mkdir -p app/Infrastructure/{Persistence/Eloquent/{Models,Mapper,Repository,Query},Provider,Clock}
mkdir -p app/Presentation/Http/{Controllers,Requests,Middleware}

mv app/Models/*.php app/Infrastructure/Persistence/Eloquent/Models/ && rmdir app/Models
mv app/Http/Middleware/HandleInertiaRequests.php app/Presentation/Http/Middleware/
rm -rf app/Http
```

Lalu sesuaikan namespace berkas yang dipindah, dan dua rujukan yang menunjuk ke
lokasi lama:

- `config/auth.php` → `'model' => App\Infrastructure\Persistence\Eloquent\Models\User::class`
- setiap factory perlu `protected $model = User::class;`, dan modelnya perlu
  `protected static function newFactory()`, karena penebak otomatis Laravel
  hanya mencari factory untuk model di `App\Models`.

## 8. Daftarkan middleware dan pemetaan exception

`bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Presentation\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
->withExceptions(function (Exceptions $exceptions): void {
    // Pelanggaran aturan bisnis tampil sebagai error form biasa,
    // sehingga domain tidak pernah menyentuh HTTP.
    $exceptions->dontReport(DomainRuleViolation::class);
    $exceptions->map(fn (DomainRuleViolation $e) => ValidationException::withMessages([
        $e->field() => $e->getMessage(),
    ]));
})
```

`DomainRuleViolation` adalah kelas induk abstrak di `app/Domain/Shared/Exception`
yang memuat satu method `field()` — nama kolom form tempat pesannya ditampilkan.

## 9. Satu tempat untuk semua binding

`app/Infrastructure/Provider/DomainServiceProvider.php`:

```php
class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ProductRepository::class => EloquentProductRepository::class,
        TransactionManager::class => EloquentTransactionManager::class,
        Clock::class => SystemClock::class,
    ];
}
```

Daftarkan di `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Infrastructure\Provider\DomainServiceProvider::class,
];
```

Interface-nya milik Domain/Application, implementasinya milik Infrastructure.
Selama semua kelas lain hanya meminta interface, pindah dari Eloquent ke apa pun
cukup mengubah berkas ini.

## 10. Siapkan pengujian

`phpunit.xml` — sqlite in-memory supaya tes feature cepat, plus kunci aplikasi
khusus tes supaya suite tidak bergantung pada `.env` lokal:

```xml
<env name="APP_ENV" value="testing"/>
<env name="APP_KEY" value="base64:...."/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Susunan folder tesnya:

```
tests/Unit/Domain         PHPUnit murni, tanpa Laravel (extends PHPUnit\Framework\TestCase)
tests/Unit/Application    use case + repository palsu di memori
tests/Support             repository palsu, clock beku
tests/Feature             HTTP sungguhan (extends Tests\TestCase + RefreshDatabase)
```

## 11. Slice tipis pertama

Sebelum menambah fitur sungguhan, buat satu potong tipis untuk membuktikan
semua lapisan tersambung. Urutannya selalu dari dalam ke luar:

```
app/Domain/Product/Entity/Product.php            entity + aturannya
app/Domain/Product/Repository/ProductRepository.php    interface
app/Application/Product/Query/ProductList.php          kontrak baca
app/Infrastructure/.../Repository/EloquentProductRepository.php
app/Infrastructure/.../Query/EloquentProductList.php
app/Presentation/Http/Controllers/ProductController.php
resources/js/Pages/Products.jsx
```

Controller-nya tinggal begini — tanpa Eloquent, tanpa aturan bisnis:

```php
class ProductController extends Controller
{
    public function index(ProductList $products): Response
    {
        return Inertia::render('Products', ['products' => $products->all()]);
    }
}
```

## 12. Jalankan dan periksa

```bash
cp .env.example .env && php artisan key:generate
php artisan migrate
composer dev          # server + vite (atau: php artisan serve & npm run dev)
php artisan test
```

Setup dianggap beres kalau: halaman terbuka tanpa error di konsol browser,
`php artisan test` hijau, dan tes di `tests/Unit/Domain` lolos **tanpa**
`RefreshDatabase` — itu bukti domain benar-benar lepas dari framework.

## Jebakan yang sudah kami tabrak

| Gejala | Sebabnya |
| --- | --- |
| `Unable to locate file in Vite manifest: resources/css/app.css` | CSS ikut didaftarkan di `@vite` padahal sudah di-`import` dari `app.jsx` |
| Tes feature gagal “Not a valid Inertia response” | biasanya error Vite manifest di atas, atau aset belum di-`npm run build` |
| `Class "App\User" not found` saat `Model::factory()` | factory belum punya `protected $model` setelah model dipindah dari `App\Models` |
| `Declaration of ...::data() must be compatible with Request::data()` | method di FormRequest bertabrakan dengan milik `Illuminate\Http\Request`; pakai nama lain, mis. `productData()` |
| Fetch ke API balas 419 | token CSRF tidak ikut; kirim header `X-XSRF-TOKEN` dari cookie `XSRF-TOKEN` |
| `composer install` putus di jaringan lambat | batas waktunya dari `default_socket_timeout` PHP, bukan `COMPOSER_REQUEST_TIMEOUT` |

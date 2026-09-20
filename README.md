# Warung App

Aplikasi kasir sederhana untuk warung kecil. Laravel 12 + Inertia + React +
TanStack Query, database MySQL.

Lingkup MVP dan daftar backlog ada di [CATATAN.md](CATATAN.md). Struktur kode
(DDD: domain, use case, infrastruktur) ada di [ARSITEKTUR.md](ARSITEKTUR.md).
Panduan pasang di server ada di [DEPLOY.md](DEPLOY.md).

## Fitur (hanya 4)

| Halaman | Rute | Fungsi |
| --- | --- | --- |
| Kasir | `/` | Ketuk produk → keranjang → **Selesai**. Stok otomatis berkurang. |
| Produk | `/products` | Tambah & edit produk beserta harga jual/beli. |
| Stok Masuk | `/stock-in` | Catat barang datang, stok bertambah dan tercatat. |
| Laporan | `/report` | Penjualan hari ini, laba kotor, produk terlaris, stok menipis. |

## Tabel

Nama tabel dan kolom memakai bahasa Inggris; teks yang dilihat pemilik warung tetap
bahasa Indonesia.

- `products` — name, cost_price, sell_price, stock
- `transactions` — code (pengelompok 1 kali checkout), product_id, qty, price, cost_price, total, sold_at
- `stock_ins` — product_id, qty, date
- `users` — akun pemilik (disiapkan untuk multi-toko nanti)

## Jalankan di lokal

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate

mysql -u root -p -e "CREATE DATABASE warung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
# sesuaikan DB_USERNAME / DB_PASSWORD di .env

php artisan migrate --seed   # seeder membuat akun pemilik + 10 produk contoh (env local)
composer dev                 # server + vite
```

Buka http://localhost:8000 lalu masuk dengan email/password dari `WARUNG_USER_*` di `.env`
(default `warung@example.com` / `rahasia123` — **ganti sebelum dipakai di warung**).

## Struktur kode

```
app/Domain          aturan bisnis murni (tanpa Laravel)
app/Application     use case + DTO + kontrak read model
app/Infrastructure  Eloquent, query, binding container
app/Presentation    controller, form request, middleware
```

Seluruh nama kelas, method, variabel, kolom, rute, dan props memakai bahasa Inggris.

Halaman dirender Inertia; datanya dipegang TanStack Query lewat API JSON di
`/api/*` (props Inertia jadi `initialData`, mutasi pakai `useMutation` +
`invalidateQueries`). Daftar endpoint ada di [ARSITEKTUR.md](ARSITEKTUR.md).

Penjelasan lengkap beserta alur satu transaksi ada di [ARSITEKTUR.md](ARSITEKTUR.md),
termasuk lampiran **tutorial setup awal** kalau mau memulai project baru dengan
pola yang sama (Laravel + Inertia + React + DDD).

## Tes

```bash
php artisan test
```

- `tests/Unit/Domain` dan `tests/Unit/Application` jalan tanpa database.
- `tests/Feature` menguji halaman Inertia dan API JSON lewat sqlite in-memory.

## Pengaturan

- `LOW_STOCK_THRESHOLD` (default 5) — batas produk masuk daftar "stok menipis".
- `APP_TIMEZONE` (default `Asia/Jakarta`) — menentukan batas "hari ini" pada laporan.

## Catatan pemasangan

`laravel/pint` (formatter) sengaja tidak dipasang karena unduhan paketnya dari GitHub
gagal berulang kali di jaringan tempat project ini dibuat. Kalau butuh:
`composer require --dev laravel/pint`.

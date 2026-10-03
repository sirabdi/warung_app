# Catatan Scope — Warung App (MVP)

## Yang dikerjakan sekarang (hanya 4 ini)

1. **Tambah/edit produk & harga** — `/products` (setiap produk wajib punya kategori)
2. **Catat stok masuk** — `/stock-in`
3. **Transaksi jual (stok otomatis berkurang)** — `/` (kasir)
4. **Laporan penjualan harian sederhana** — `/report`

Tambahan atas permintaan pemilik (Oktober 2026):

- **Kategori produk (CRUD)** — `/categories`. 11 kategori default dibuat oleh
  `CategorySeeder`; produk lama dimasukkan ke kategorinya oleh
  `ProductCategorySeeder`. Kategori yang masih dipakai produk tidak bisa dihapus.
- **Satuan timbang (kg/liter)** — beras dan minyak curah ditimbang di depan
  pembeli. Produk bisa bersatuan `pcs`, `kg`, atau `liter`; kasir menerima berat
  atau nominal uang ("minyak 10 ribu"), total barang timbang dibulatkan ke
  Rp 100. `Beras 1kg` dan `Minyak Goreng 1L` dikonversi oleh
  `WeighedProductSeeder` (10 pcs → 10 kg).

Login satu akun pemilik ikut dibuat karena aplikasi dipasang online dan datanya
tidak boleh terbuka untuk publik. Tabel `users` sudah siap untuk multi-toko,
tapi **logika multi-toko belum dikerjakan**.

## Backlog — JANGAN dikerjakan dulu

Semua di bawah ini sudah dipikirkan tapi sengaja ditunda sampai MVP dipakai
nyata di 1 warung minimal 1–2 minggu:

- Multi-toko / multi-user per toko (kolom `store_id`, pemisahan data)
- Hitung uang bayar & kembalian di kasir
- Diskon, promo, harga grosir, konversi satuan beli ↔ jual (dus/karung → pcs/kg)
- Barcode scanner & kode produk
- Cetak struk / printer bluetooth
- Hapus produk & batalkan/refund transaksi
- Retur barang, stok opname, koreksi stok manual
- Foto produk
- Utang/kasbon pelanggan
- Laporan mingguan/bulanan, grafik tren, ekspor Excel
- Notifikasi WhatsApp stok habis
- Mode offline / PWA
- Pencatatan pengeluaran & kas
- Backup otomatis terjadwal

## Keputusan desain yang dipilih sadar

- **Stok tidak bisa diedit manual** lewat halaman Produk. Setiap perubahan stok
  harus lewat Stok Masuk atau penjualan, supaya angka stok selalu punya jejak.
  (Koreksi stok manual = backlog.)
- **Penjualan ditolak kalau stok kurang.** Lebih baik pemilik menyadari datanya
  meleset sejak awal daripada stok jadi minus diam-diam.
- **Harga jual disimpan ulang di tiap transaksi** (`transactions.harga`), jadi
  laporan lama tidak berubah saat harga produk diubah.
- **Kasir mengurutkan produk berdasarkan yang paling laku 30 hari terakhir**,
  bukan abjad — mengurangi waktu mencari.
- Target kecepatan: 1 transaksi ≤ 10 detik → ketuk produk → **Selesai**.
- **Struktur kode DDD berlapis** (domain / use case / infrastruktur / presentasi).
  Aturan bisnis dikumpulkan di `app/Domain` supaya tidak tercecer di controller,
  dan bisa diuji tanpa database. Detailnya di [ARSITEKTUR.md](ARSITEKTUR.md).
  Ini perubahan struktur, **bukan penambahan fitur** — lingkup tetap 4 fitur di atas.
- **Data di frontend dipegang TanStack Query**, Inertia tetap untuk routing,
  sesi, dan CSRF. Props Inertia jadi `initialData` supaya halaman tidak pernah
  menampilkan spinner saat dibuka, lalu daftar produk/stok disegarkan sendiri
  dan setiap mutasi memicu `invalidateQueries`. API JSON ditaruh di grup `web`
  agar tidak perlu lapisan autentikasi kedua (Sanctum/token).
- **Kode berbahasa Inggris, tampilan berbahasa Indonesia.** Nama kelas, method,
  variabel, kolom database, rute, dan props semuanya Inggris; semua teks yang
  dibaca pemilik warung (tombol, label, pesan error) tetap Indonesia.

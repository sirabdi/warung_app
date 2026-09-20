# Deploy & Uji Coba di 1 Warung

Target: aplikasi jalan di satu server, pemilik warung membukanya lewat HP/tablet.

## 1. Siapkan server

Minimal: 1 vCPU / 1 GB RAM (cukup untuk satu warung). PHP 8.2+, MySQL 8, Nginx, Node 20.

```bash
sudo apt install -y nginx mysql-server php8.3-{fpm,mysql,mbstring,xml,curl,zip,bcmath} unzip git
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

## 2. Database

```sql
CREATE DATABASE warung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'warung'@'localhost' IDENTIFIED BY 'ganti-password-ini';
GRANT ALL PRIVILEGES ON warung.* TO 'warung'@'localhost';
```

## 3. Pasang aplikasi

```bash
cd /var/www && sudo git clone <repo> warung-app && cd warung-app
composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

Ubah di `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://warung.domainmu.com
DB_DATABASE=warung
DB_USERNAME=warung
DB_PASSWORD=ganti-password-ini
WARUNG_USER_EMAIL=email-pemilik@contoh.com
WARUNG_USER_PASSWORD=password-yang-kuat
```

```bash
php artisan migrate --force
php artisan db:seed --force     # membuat akun pemilik
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 4. Nginx + HTTPS

`root` diarahkan ke `/var/www/warung-app/public` (konfigurasi standar Laravel), lalu:

```bash
sudo certbot --nginx -d warung.domainmu.com
```

HTTPS wajib: tanpa itu sesi login lewat WiFi warung bisa dibajak.

## 5. Backup harian (wajib sebelum dipakai nyata)

```
0 1 * * * mysqldump -u warung -p'...' warung | gzip > /var/backups/warung-$(date +\%F).sql.gz
0 2 * * * find /var/backups -name 'warung-*.sql.gz' -mtime +14 -delete
```

## 6. Hari pertama di warung

1. Buka aplikasi di HP pemilik → **Tambah ke Layar Utama** supaya seperti aplikasi biasa.
2. Input 20–30 produk paling laku dulu, jangan semua. Cukup nama + harga jual.
3. Isi stok lewat **Stok Masuk** untuk produk yang sudah diinput.
4. Dampingi transaksi 30 menit pertama, catat di mana pemilik ragu atau salah ketuk.
5. Sore hari, buka **Laporan** bersama pemilik dan cocokkan dengan uang di laci.

## 7. Yang diukur selama uji coba

- Berapa detik rata-rata satu transaksi (target < 10 detik).
- Berapa kali penjualan tertahan karena stok tercatat kurang.
- Selisih laporan vs uang laci di akhir hari.
- Fitur apa yang paling sering diminta → baru itu diangkat dari backlog.

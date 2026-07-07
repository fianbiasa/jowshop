# Jowshop

Platform sales funnel — salespage, checkout, order bump/upsell/downsell,
pengiriman (RajaOngkir/Komerce & Biteship), pembayaran (Duitku), dan
dashboard analitik dalam satu aplikasi.

> **Catatan penting**: aplikasi ini **tidak multi-tenant**. Semua data
> (pesanan, funnel, produk, kredensial Settings) bersifat global — siapapun
> yang login punya akses penuh ke semuanya. Ini dirancang untuk dipakai
> **satu admin per instalasi**. Fitur registrasi publik sengaja dimatikan
> (lihat `config/fortify.php`) karena alasan ini — jangan diaktifkan lagi
> tanpa membangun pemisahan data per-user terlebih dahulu.

## Tech Stack

| Layer      | Teknologi                                    |
| ---------- | --------------------------------------------- |
| Backend    | PHP 8.4, Laravel 13                           |
| Frontend   | Inertia.js v3, React 19, TypeScript, Tailwind v4 |
| Database   | MySQL                                          |
| Auth       | Laravel Fortify (2FA, passkey — registrasi mati) |
| Pembayaran | Duitku                                         |
| Pengiriman | RajaOngkir/Komerce, Biteship                   |
| Build tool | Vite + Laravel Wayfinder                       |

## Requirements

- PHP 8.3+ (8.4 direkomendasikan) dengan ekstensi standar Laravel: BCMath,
  Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO,
  PDO MySQL, Session, Tokenizer, XML
- Composer 2.x
- Node.js 20+ dan npm
- MySQL 8+ (atau MariaDB setara)
- Akses cron (buat scheduler & queue worker)

Aplikasi ini **tidak butuh** Redis, Imagick/GD, atau ekstensi eksotis
lainnya — session, cache, dan queue semua jalan lewat driver `database`.

## Environment Variables Penting

Salin `.env.example` ke `.env`, lalu isi minimal:

```
APP_URL=https://domainmu.com
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
```

`APP_KEY` dibuat otomatis lewat `php artisan key:generate` — **jangan**
ganti/hapus setelah aplikasi berjalan, karena semua kredensial di Settings
(Meta CAPI access token, API key Shipping/Payment/AI Provider) dienkripsi
pakai key ini. Kalau `APP_KEY` berubah, semua kredensial itu jadi tidak
bisa dibaca lagi (harus diisi ulang manual).

## Instalasi (Langkah Umum — Berlaku di Semua Hosting)

```bash
git clone <url-repo-kamu> jowshop
cd jowshop

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

npm install
php artisan wayfinder:generate --with-form
npm run build

php artisan migrate --force
php artisan storage:link
```

> Repo ini juga punya shortcut `composer setup` yang menjalankan sebagian
> besar langkah di atas otomatis (install, copy `.env`, `key:generate`,
> `migrate`, `npm install`, `npm run build`) — masih perlu jalanin
> `storage:link` dan `wayfinder:generate --with-form` manual sesudahnya.
> Untuk instalasi pertama kali, lebih disarankan jalankan manual satu-satu
> seperti di atas dulu supaya gampang ketauan kalau ada langkah yang gagal
> (umum terjadi di shared hosting).

Bikin akun admin pertama (registrasi publik mati by design):

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Nama Kamu',
    'email' => 'kamu@domain.com',
    'password' => Hash::make('password-yang-kuat'),
    'email_verified_at' => now(),
]);
```

Cron (wajib, di semua tipe hosting — satu baris ini menjalankan reminder
pembayaran otomatis **dan** queue worker Meta CAPI sekaligus, tanpa perlu
Supervisor/proses background permanen):

```
* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
```

Setelah login pertama kali: buka **Settings** dan isi kredensial Shipping
(RajaOngkir/Komerce atau Biteship), Payment (Duitku), Meta CAPI, dan AI
Provider sesuai kebutuhan — semuanya kosong di instalasi baru, itu memang
seharusnya begitu.

---

## Setup di VPS

Paling fleksibel — kamu kontrol penuh web server, PHP-FPM, dan cron.

1. **PHP & ekstensi**: install PHP 8.4 + ekstensi di atas lewat package
   manager (`apt`, `dnf`, dll), atau pakai repo pihak ketiga (mis. Ondřej
   Surý untuk Debian/Ubuntu).
2. **Web server** — Nginx (direkomendasikan) atau Apache, **document root
   HARUS mengarah ke folder `public/`**, bukan root proyek. Contoh Nginx:

   ```nginx
   server {
       listen 80;
       server_name domainmu.com;
       root /var/www/jowshop/public;

       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/run/php/php8.4-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

3. **SSL**: pakai Certbot (`certbot --nginx -d domainmu.com`) — Duitku dan
   Biteship webhook keduanya butuh HTTPS.
4. **Permission**: `storage/` dan `bootstrap/cache/` harus writable oleh
   user yang menjalankan PHP-FPM (biasanya `www-data`):
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   ```
5. **Cron**: tambahkan lewat `crontab -e` (user yang sama dengan PHP-FPM,
   atau `www-data` lewat `crontab -e -u www-data`).
6. Ikuti langkah instalasi umum di atas.

## Setup di Hosting cPanel

cPanel biasanya tidak kasih akses root, tapi punya fitur "Setup Node.js
App" dan "MultiPHP Manager" yang cukup buat aplikasi ini.

1. **Buat domain/subdomain** lewat menu Domains — **arahkan document root
   ke `public_html/jowshop/public`** (bukan langsung ke folder proyek).
   Kalau cPanel tidak izinkan document root custom di luar `public_html`,
   taruh seluruh proyek di `public_html/jowshop` lalu buat symlink atau
   `.htaccess` redirect dari `public_html/index.php` ke
   `public_html/jowshop/public/index.php` (cara paling bersih tetap lewat
   subdomain dengan document root custom kalau tersedia).
2. **PHP version**: pilih PHP 8.4 lewat "MultiPHP Manager" atau "Select
   PHP Version" untuk domain tersebut.
3. **Terminal/SSH**: kalau paket hosting kamu ada akses SSH/Terminal
   (banyak paket cPanel modern menyediakan ini), jalankan langkah
   instalasi umum di atas langsung. Kalau tidak ada SSH:
   - Upload project via Git Version Control (menu **Git™ Version
     Control** di cPanel, kalau tersedia) atau upload manual/File Manager.
   - Jalankan `composer install` lewat menu **"Setup Node.js App"** →
     bagian PHP biasanya juga punya opsi jalankan command, atau lewat
     **Terminal** app bawaan cPanel kalau ada.
   - `npm install && npm run build` perlu Node.js — aktifkan lewat
     **"Setup Node.js App"**, pilih versi Node 20+, lalu jalankan
     command build dari sana.
4. **Database**: buat database + user MySQL lewat **MySQL® Databases**,
   isi ke `.env`.
5. **Cron Job**: menu **Cron Jobs** di cPanel, tambahkan:
   ```
   * * * * * cd /home/USERNAME/public_html/jowshop && php artisan schedule:run >> /dev/null 2>&1
   ```
6. **SSL**: aktifkan **AutoSSL** (gratis, otomatis) lewat menu SSL/TLS.
7. Ikuti langkah instalasi umum (migrate, storage:link, wayfinder,
   bikin admin pertama).

## Setup di Hosting DirectAdmin

Mirip cPanel, panel & istilahnya sedikit beda.

1. **Buat domain/subdomain** lewat menu **Domain Setup** — set document
   root ke folder `public/` di dalam proyek (DirectAdmin biasanya
   mengizinkan custom document root per domain/subdomain lewat opsi
   "Custom" saat setup domain).
2. **PHP version**: pilih PHP 8.4 lewat **PHP Selector** (kalau vendor
   hosting kamu menyediakan JetBackup/CloudLinux PHP Selector — hampir
   semua DirectAdmin modern punya ini).
3. **SSH**: DirectAdmin pada dasarnya server sungguhan (bisa shared
   maupun dedicated) — kalau kamu punya akses SSH (umum di DirectAdmin,
   beda dengan banyak cPanel shared hosting), jalankan langkah instalasi
   umum di atas langsung lewat terminal.
4. **Database**: buat database + user MySQL lewat menu **MySQL
   Management**.
5. **Cron Job**: menu **Cron Jobs**, tambahkan:
   ```
   * * * * * cd /home/USERNAME/domains/domainmu.com/jowshop && php artisan schedule:run >> /dev/null 2>&1
   ```
6. **SSL**: aktifkan **Let's Encrypt** lewat menu SSL Certificates
   (biasanya satu klik di DirectAdmin).
7. Ikuti langkah instalasi umum (migrate, storage:link, wayfinder, bikin
   admin pertama).

---

## Setelah Instalasi

- **Settings → Pengiriman**: pilih provider (Komerce/RajaOngkir atau
  Biteship), isi API key, ID area asal. Kalau pakai Biteship dan mau
  booking otomatis, isi juga kontak & alamat asal, lalu aktifkan toggle
  "Booking Otomatis".
- **Settings → Payment**: isi kredensial Duitku (merchant code, API key).
- **Settings → Meta CAPI**: isi Pixel ID + access token kalau mau tracking
  iklan Facebook/Instagram.
- **Settings → AI Provider**: isi API key OpenAI/Anthropic/Gemini kalau
  mau pakai fitur generate salespage dengan AI.

Semua Settings di atas **kosong di instalasi baru** — ini normal, bukan
bug, karena setiap instalasi dianggap toko/bisnis yang terpisah.

## Menjalankan Test

```bash
php artisan test --compact
```

## Development Lokal

```bash
composer run dev
```

Menjalankan server PHP, queue listener, log viewer (Pail), dan Vite dev
server sekaligus.

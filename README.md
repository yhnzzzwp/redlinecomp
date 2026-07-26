# Redline Komputer — Sistem Informasi (SIRC)

Platform web penjualan (POS), servis/reparasi, dan manajemen stok Toko Redline Komputer.
Dibangun sesuai **SRS Kelompok 5 · RPL** dengan pola **MVC (Laravel)**.

## Stack
- **Laravel 13** (PHP 8.3) · Blade · Bootstrap 5 · Alpine.js
- **MySQL 8** (via Docker)
- **laravel-dompdf** — nota digital PDF
- Autentikasi sesi (bcrypt) · enforcement role di server
- **Tiga portal terpisah per subdomain** — publik, karyawan, admin (sesi terisolasi per host)
- Design system "Instrument Panel" — Barlow/Barlow Condensed + IBM Plex Mono (self-hosted)

## Menjalankan (Docker — direkomendasikan)
```bash
cp .env.example .env         # lalu isi APP_KEY: php artisan key:generate
docker compose up -d --build            # PHP-FPM + Nginx + MySQL 8 + Adminer
docker compose exec app php artisan migrate --seed
```

| Portal | URL | Untuk |
|--------|-----|-------|
| Situs publik | http://localhost:8080 | Customer (katalog, cek servis/nota) |
| Portal Karyawan | http://karyawan.localhost:8080 | Login karyawan (POS, produk, servis) |
| Admin Console | http://admin.localhost:8080 | Login owner (semua + analytics, promo, pegawai) |

- Adminer (GUI DB): http://localhost:8081  (server: `db`, user: `redline`, pass: `redline_secret`)
- `/login` sengaja **404 di host publik** — gerbang masuk hanya lewat subdomain portal.

## Kredensial demo
| Peran | Portal login | Username | Password |
|-------|--------------|----------|----------|
| Owner | admin.localhost:8080 | `owner`  | `password` |
| Karyawan | karyawan.localhost:8080 | `rijal` | `password` |

## Dokumentasi
Lihat folder [`docs/`](docs/) — arsitektur, ERD, setup, keamanan, panduan fitur.

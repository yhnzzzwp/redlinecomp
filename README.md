# Redline Komputer — Sistem Informasi (SIRC)

Platform web penjualan (POS), servis/reparasi, dan manajemen stok Toko Redline Komputer.
Dibangun sesuai **SRS Kelompok 5 · RPL** dengan pola **MVC (Laravel)**.

## Stack
- **Laravel 13** (PHP 8.3) · Blade · Bootstrap 5 · Alpine.js
- **MySQL 8** (via Docker)
- **laravel-dompdf** — nota digital PDF
- Autentikasi sesi (bcrypt) · enforcement role di server

## Menjalankan (Docker — direkomendasikan)
```bash
cp .env.example .env         # lalu isi APP_KEY: php artisan key:generate
docker compose up -d --build            # PHP-FPM + Nginx + MySQL 8 + Adminer
docker compose exec app php artisan migrate --seed
```
- Aplikasi: http://localhost:8080
- Adminer (GUI DB): http://localhost:8081  (server: `db`, user: `redline`, pass: `redline_secret`)

## Kredensial demo
| Peran | Username | Password |
|-------|----------|----------|
| Owner | `owner`  | `password` |
| Karyawan | `rijal` | `password` |

## Dokumentasi
Lihat folder [`docs/`](docs/) — arsitektur, ERD, setup, keamanan, panduan fitur.

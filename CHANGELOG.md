# Changelog — SIRC

## [Unreleased]
### Ditambahkan
- Setup proyek Laravel 13 + Docker stack (PHP-FPM, Nginx, MySQL 8, Adminer).
- 9 migrasi + 9 model Eloquent + 5 enum sesuai ERD SRS.
- Seeder data demo (5 pegawai, 8 produk, 3 promo, 1 transaksi, 1 servis + riwayat).
- Dokumentasi awal: arsitektur, ERD, setup, keamanan, panduan fitur.
- Konfigurasi keamanan: fillable eksplisit, php.ini hardening, header nginx, session 30 menit.
- Autentikasi sesi (login username/email, bcrypt), rate-limit anti brute-force, regenerate session.
- Role gate server-side (middleware EnsureOwner) + security headers middleware.
- Frontend: Bootstrap 5 + Alpine via Vite; design system Redline (resources/css/app.css).
- Blade components: layout internal + sidebar (anti-duplikasi) + topbar.
- Halaman Login, Dashboard (data live), Landing publik minimal — berjalan end-to-end.

## [POS] — Pengkasiran
### Ditambahkan
- Standar kode: seluruh PHP kini `declare(strict_types=1)`, tanpa komentar, kelas `final`, DTO `readonly`, enum, match.
- Backend POS aman: PosService (DB transaction + lockForUpdate, harga server-side), PromoService, KodeGenerator.
- DTO (CartLine, CheckoutData, PromoResult) + exceptions domain (Stok/Promo/Pembayaran).
- StoreTransaksiRequest (validasi + mapping DTO), PosController (index/checkout/nota).
- Nota digital PDF via laravel-dompdf (resources/views/pdf/nota.blade.php).
- Halaman POS fungsional (Alpine: keranjang, filter kategori, promo, checkout).
- Uji: 5 feature test lulus. Kualitas: Larastan level 5 (0 error) + phpstan.neon.

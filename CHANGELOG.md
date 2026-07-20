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

## [Produk] — Manajemen Produk (CRUD)
### Ditambahkan
- CRUD Produk penuh: ProdukController (resource), ProductService (persist + simpan/hapus foto).
- StoreProdukRequest & UpdateProdukRequest (validasi + validasi upload gambar mimes/max, SKU unik).
- View index (tabel + cari + pagination Bootstrap + badge stok) & form (create/edit) + konfirmasi hapus.
- Flash message sukses/error di layout admin. storage:link untuk foto produk.
- phpMyAdmin ditambahkan ke Docker stack (port 8082) untuk inspeksi database.
- Uji: 5 feature test CRUD (validasi, unik, upload, update, hapus). Larastan level 5 tetap 0 error.

## [Servis] — Manajemen Servis
### Ditambahkan
- Fitur servis penuh: ServiceTicketService (buat tiket + resi otomatis, update status, tambah sparepart) — semua via DB transaction.
- Nomor resi otomatis anti-duplikat (KodeGenerator::resi, format PK-YYYY-NNNN).
- Riwayat status disimpan (service_status), tidak ditimpa — sesuai SRS §2.5.
- View index (tabel + filter status + cari), form tambah, dan Detail Servis (stepper 5 tahap, timeline riwayat, sparepart) — melengkapi layar yang hilang di Figma.
- Requests: StoreServiceRequest, UpdateStatusServiceRequest, StorePartServiceRequest.
- Hapus ExampleTest bawaan. Uji: 5 feature test servis. Total 15 test lulus, Larastan 0 error.

## [Promo] — Manajemen Promo (Owner)
### Ditambahkan
- CRUD Promo khusus Owner (enforcement ganda: middleware 'owner' + FormRequest::authorize isOwner).
- StorePromoRequest & UpdatePromoRequest: kode unik + auto-kapital, tipe enum, batas persen 1–100, validasi periode.
- View index (kartu promo gradient + status berlaku) & form create/edit.
- Uji: 6 feature test termasuk Karyawan ditolak (403). Total 21 test lulus, Larastan 0 error.

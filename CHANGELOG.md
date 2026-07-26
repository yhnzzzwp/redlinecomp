# Changelog — SIRC

## [Impor Excel] — Input data produk via Excel (menggantikan CSV)
### Ditambahkan
- **Impor produk .xlsx/.xls** (revisi permintaan): `ProdukExcelService` berbasis PhpSpreadsheet 5 — header fleksibel (alias `Barang`/`Kode Barang`/`QTY`/`HPP` dsb.), format "Rp 3.100.000" diterima, upsert ber-kunci SKU, kategori dibuat otomatis, batas 2.000 baris.
- Impor bersifat **all-or-nothing**: seluruh baris divalidasi dulu; bila ada yang bermasalah, tidak ada yang diimpor dan galat per baris ditampilkan (`Baris N: …`).
- **Ekspor Produk (.xlsx)** — alur kerja Owner: ekspor → sesuaikan di Excel → impor kembali.
- **Template .xlsx** dengan baris contoh, sheet "Petunjuk", dropdown kategori (data validation), header beku.
- Mitigasi formula injection: semua sel teks ditulis bertipe string eksplisit + quote-prefix untuk sel berawalan `= + - @`.
- 6 test baru (impor sukses, alias header + rupiah, upsert SKU, all-or-nothing, CSV ditolak, unduh template/ekspor).
### Diubah
- Route `produk.template`/`produk.import` kini melayani Excel (path `template-excel`/`import-excel`); tambah `produk.export`.
- Logika impor dipindah dari controller ke service (melunasi refactor T3); `ProdukController` menyusut ±100 baris.
### Dihapus
- Impor & template CSV — **CSV tidak lagi diterima** (pesan galat mengarahkan ke template Excel).

## [Tema Terang] — Zona publik terang + beranda katalog
### Diubah
- Tema utama zona publik menjadi **terang**; carbon gelap tersisa hanya di hero beranda (banner brand) dan panel login portal.
- **Beranda langsung menampilkan katalog** (hero + filter + grid produk + pagination); `/catalogue` dialihkan permanen ke beranda.
- Navbar publik disederhanakan menjadi tiga menu: Beranda, Lacak Servis, Tentang Kami.
- Halaman Tentang Kami: seksi Visi & Misi dirombak (daftar misi bernomor mono, jarak antar butir rapat, tanda baca dirapikan); ikon emoji 📍/💬 diganti ikon SVG stroke konsisten; ikon 🐟 kartu Toko Ikan dihapus.
### Dihapus
- Fitur publik **Cek Nota** beserta route PDF nota publik (nota tetap tersedia dari daftar transaksi internal).
- QR verifikasi di PDF nota (menunjuk halaman yang dihapus dan memanggil layanan QR pihak ketiga).

## [Portal & Redesign] — Pemisahan subdomain + design system v2
### Ditambahkan
- **Arsitektur tiga portal per subdomain**: publik (`localhost`), karyawan (`karyawan.localhost`), admin (`admin.localhost`) — config `redline.hosts`, enum `App\Support\Portal`, middleware `EnsurePortal` (prioritas sebelum `Authenticate`).
- Login per portal: role wajib cocok (Owner ↔ admin, Karyawan ↔ karyawan), pesan galat generik, rate-limit per portal+username+IP, log login gagal.
- Zona internal tersembunyi dari host publik (404); halaman publik di host portal dialihkan ke login; sesi terisolasi per host (cookie host-only).
- Keamanan: `TrustHosts` (produksi), `X-Robots-Tag: noindex` + `robots.txt` dinamis di host portal, `Cross-Origin-Opener-Policy`, font self-hosted (CSP `font-src 'self'` tanpa CDN).
- **Design system v2 "Instrument Panel"** (`resources/css/app.css` ditulis ulang): tipografi Barlow + Barlow Condensed + IBM Plex Mono (self-host, subset latin), motif tick tachometer & stripe livery, zona publik gelap carbon, portal kerja terang, identitas sidebar per portal (admin = carbon, karyawan = terang) + chip portal.
- Redesign seluruh view publik (landing hero baru + stat readout, katalog, detail, cek servis/nota, about, 404, soon), login split-panel sadar-portal, sidebar/topbar, aksen mono untuk SKU/resi/nota di view internal.
- Test baru `PortalSeparationTest` (6 skenario lintas portal); total 57 test lulus, Larastan 0 error.
### Diperbaiki
- Reveal-on-scroll: elemen yang terlewat scroll cepat kini tetap muncul (rootMargin atas besar).
- View transition menggantung saat tab tersembunyi: guard `pageswap`/`pagereveal` + watchdog `skipTransition`.
- Glyph unduh `⭳` (tofu di font baru) diganti ikon SVG.

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

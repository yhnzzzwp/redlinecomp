# Changelog — SIRC

## [P3 Akuntansi — poles hasil review] — Integritas servis & jurnal
### Diperbaiki
- **POS kini menagih `totalBiaya()` servis (jasa + part)** — sebelumnya hanya `biaya_service`, padahal halaman servis, pesan WA, dan cek servis publik menjanjikan total termasuk part (bug kurang tagih).
- **Mutasi stok part servis lewat `StokService`** (tipe baru `Part Servis`): pasang part & batalkan part kini tercatat di riwayat mutasi — sebelumnya decrement/increment langsung (melanggar aturan satu-pintu §4.6).
- **Snapshot `harga_modal`** di `item_transaksi` & `part_service` saat transaksi/pasang part — HPP jurnal tidak lagi berubah retroaktif saat harga modal diedit atau produk dihapus; jurnal membaca snapshot (fallback terdokumentasi untuk data lama).
- **HPP part servis ikut dijurnal** (pasangan HPP ↔ Persediaan) — saldo Persediaan di buku tidak lagi menyimpang dari stok fisik saat servis berpart dibayar.
- Ekspor jurnal & produk memakai `lazy()` (bukan `cursor()` yang diam-diam mengabaikan eager load — N+1); periode jurnal dibatasi 1 tahun + validasi tanggal (galat ramah, bukan 500); baris kas 0 tidak ditulis; quote-prefix formula-injection kini di semua sheet; sheet Info memperingatkan void-setelah-ekspor.
- 3 test baru + 2 diperkuat — total 94 test / 343 assertion.

## [P3 Akuntansi] — Ekspor Jurnal Akuntansi (.xlsx)
### Ditambahkan
- **Ekspor Jurnal Akuntansi** di Analytics (Owner): jurnal umum **double-entry per transaksi** siap dipetakan ke software akuntansi (Accurate/Zahir/Jurnal.id) — `JurnalExcelService` (PhpSpreadsheet, pola sama dengan ekspor produk termasuk mitigasi formula-injection).
- Per transaksi Normal: debit Kas/Bank/QRIS (per metode bayar) + debit Diskon Penjualan, kredit Pendapatan Penjualan Produk / Pendapatan Jasa Servis, plus pasangan **HPP ↔ Persediaan** dari `harga_modal` — setiap blok dijamin **seimbang** (debit = kredit); Void/Refund dikecualikan.
- 3 sheet: **Jurnal** (baris per akun + baris TOTAL), **Rekap Akun** (neraca percobaan periode), **Info** (periode, catatan HPP-0 bila `harga_modal` kosong).
- **Bagan akun dapat disesuaikan** akuntan tanpa menyentuh kode: `config/redline.php` bagian `akun` (kode + nama per akun).
- Route `analytics/export-jurnal` (grup Owner) + tombol "Jurnal Akuntansi" di halaman Analytics (ikut filter periode).
- 4 test baru (jurnal seimbang & nilai per akun pada penjualan campuran produk+servis+promo, Void & luar-periode dikecualikan, file kosong tetap valid, Karyawan 403) — total 91 test.

## [P3 PWA] — POS dapat di-install (Add to Home Screen)
### Ditambahkan
- **Manifest PWA** (`/manifest.webmanifest`) disajikan via route di grup `portal:internal` — dari host publik **404** (zona internal tetap tersembunyi), nama aplikasi mengikuti portal (`SIRC POS · Portal Karyawan` / `· Admin Console`), `start_url` ke `/pos`, tampil `standalone`; tanpa auth karena browser mengambil manifest tanpa cookie sesi.
- **Ikon PWA** (`public/icons/`): 192/512 + varian maskable + `apple-touch-icon` — monogram RL Barlow Condensed 800 Italic + bar merah bercelah 18° di atas carbon-950, digenerate lokal via `scripts/buat-ikon-pwa.php` (PHP GD + font self-host proyek; nol aset pihak ketiga).
- **Meta pemasangan** di layout internal (`theme-color`, `link manifest`, `apple-touch-icon`, `mobile-web-app-capable` + varian apple) **dan link manifest di halaman login/2FA** — kasir bisa memasang POS dari halaman pertama di perangkat baru; layout publik tidak disentuh.
- Smoke test produksi: +3 cek (manifest 200 di portal karyawan & admin, 404 di host publik) — kini 14 cek lokal.
- 7 test baru (manifest per portal, 404 publik, ikon ada **+ dimensi cocok deklarasi `sizes`**, tag di layout internal & login, publik bersih) — total 87 test.
### Catatan
- Service worker/offline **sengaja belum dibuat** (CSP ketat; dievaluasi belakangan sesuai docs/09 §3).

## [P3 Stok] — Stok opname + riwayat mutasi stok (jejak audit barang)
### Ditambahkan
- **Tabel `mutasi_stok`** — jejak audit setiap pergerakan barang: sebelum → sesudah (selisih ±), tipe, keterangan, pegawai. Tercatat otomatis dari **5 titik**: penjualan POS (`Nota #…`), void transaksi, edit produk (hanya bila stok berubah), impor Excel, dan opname.
- **Halaman Stok Opname** (menu "Stok", semua staf): daftar produk + input stok fisik, selisih & penghitung live, catatan opname, konfirmasi sebelum simpan; hanya baris terisi yang diproses, dengan `lockForUpdate` per produk.
- **Halaman Riwayat Mutasi**: filter tipe/pencarian produk/per-produk (tombol "Riwayat" di tabel produk), paginasi, pill berwarna per tipe.
- `StokService` sebagai satu pintu pencatatan (perubahan tanpa selisih tidak dicatat); enum `TipeMutasiStok`.
- 7 test baru (opname menyesuaikan + mencatat, POS mencatat penjualan, void mengembalikan + mencatat, edit produk selektif, impor tercatat, halaman & filter) — total 80 test.

## [P3 Fitur Bisnis] — WA servis, 2FA Owner, struk thermal, laba per produk
### Ditambahkan
- **Notifikasi WhatsApp status servis** (`App\Support\Wa`): tombol "Kirim Update WA" di halaman servis membuka wa.me dengan pesan terisi sesuai status terkini (template per status, nomor dinormalisasi 0→62, tautan lacak publik) — tanpa API pihak ketiga.
- **2FA TOTP untuk Owner** di Admin Console (pragmarx/google2fa, RFC 6238): halaman Keamanan (aktifkan via kode, nonaktifkan via password), login ditahan di tantangan 2FA sampai kode benar, **6 kode pemulihan sekali-pakai** (ditampilkan sekali, disimpan sebagai hash), secret terenkripsi; menu sidebar "Keamanan" (Owner).
- **Struk thermal 80mm** (`/pos/struk/{trx}`): tampilan cetak monospace hemat kertas untuk printer thermal via dialog print browser; tombol "Struk" di daftar transaksi.
- **Laba per Produk** di Analytics + PDF laporan: qty, omzet, modal (dari `harga_modal`), laba, margin% — terurut laba terbesar.
- 12 test baru (WA 4 · TOTP 7 · struk 1) + assertion laba; total 73 test.

## [P2 Persiapan Go-Live] — Backup, deploy, CSP ketat, aksesibilitas
### Ditambahkan
- **Backup harian** `scripts/backup-db.sh` (mysqldump single-transaction, validasi isi, retensi otomatis 30 hari — SRS §3.5) + `scripts/restore-db.sh` (dengan konfirmasi untuk DB utama). **Restore teruji**: dipulihkan ke DB uji, jumlah baris seluruh tabel identik.
- **`scripts/smoke-produksi.sh`** — verifikasi otomatis pasca-deploy: perilaku tiga portal, noindex, robots, CSP, HSTS, cookie Secure/HttpOnly, penolakan Host asing.
- **`docs/08-deploy-produksi.md`** — prosedur lengkap: DNS tiga record, Caddy/certbot TLS, .env produksi, cache, firewall, cron backup, update rutin, pemulihan bencana.
- Test regresi CSP per portal di `PortalSeparationTest`.
### Diubah
- **CSP zona publik kini TANPA `unsafe-eval`** — interaksi publik (menu, filter katalog) ditulis ulang ke vanilla JS; portal internal (di balik login + noindex) tetap memakai Alpine.
- Kebijakan password pegawai: minimal 8 karakter **wajib huruf + angka**.
- Aksesibilitas: tautan "Lewati ke konten utama" di kedua layout, stepper servis diberi `role="list"` + `aria-current="step"`.

## [P1 Kualitas Inti] — Roadmap P1 laporan evaluasi
### Ditambahkan
- `App\Support\Uang::rupiah()` — format mata uang terpusat; 9 view kini delegasi ke satu sumber (fungsi global `rl_rp()` yang rawan redeclare dihapus).
- `App\Support\CobaUlang::unik()` — insert kode nota / nomor resi diulang otomatis saat tabrakan index unik (checkout bersamaan tidak lagi bisa gagal karena kode acak bentrok).
- `TopbarSearchComposer` — query servis aktif pindah dari blok `@php` di layout ke View Composer (view bebas logika data).
- CI: job `frontend` (npm ci + `npm audit --audit-level=high` + build Vite), `composer audit`, dan `--memory-limit=1G` untuk PHPStan.
### Diubah
- PDF nota & laporan analytics diselaraskan palet design system v2 (carbon `#15181e`, merah `#de1f26`); sisa gaya QR dihapus.
### Catatan roadmap
- "Refactor impor ke Service" telah lunas lewat fitur Impor Excel; "storage:link otomatis" gugur karena fitur unggah gambar dihapus.

## [Tanpa Gambar + Mobile] — Portal serba terang, bebas gambar, responsif HP
### Diubah
- **Sidebar portal admin ikut bertema terang** (varian carbon dihapus) — kini seluruh navbar/sidebar owner & karyawan terang; avatar topbar menyesuaikan.
- **Seluruh aplikasi tidak lagi menampilkan/mengunggah gambar**: kolom `foto_produk` & `foto_promo` di-drop dari skema (migrasi baru), upload foto dihapus dari form produk, thumbnail dihapus dari tabel produk/POS/dashboard, komponen `hardware-thumb` & CSS gambar dibuang, `ProductService` disederhanakan.
- **Responsif mobile menyeluruh** (mayoritas customer memakai HP — iOS/Android/laptop):
  - Input 16px di layar kecil (mencegah auto-zoom iOS saat fokus form).
  - Filter katalog dilipat di HP (tombol toggle), selalu terbuka di desktop.
  - Tabel data digulir horizontal di dalam kartunya — halaman tidak pernah menggulir ke samping.
  - Strip statistik hero tersusun vertikal; tombol hero membungkus; target sentuh menu publik ≥44px.
  - Padding header/login/carousel disesuaikan untuk layar sempit.

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

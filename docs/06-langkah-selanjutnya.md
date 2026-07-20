# 06 — Langkah Selanjutnya

Panduan meneruskan proyek SIRC sampai selesai & siap deploy.

## A. Status saat ini
| Fitur | Status |
|-------|--------|
| Login + role gate, Dashboard | ✅ |
| POS + Nota PDF | ✅ |
| Manajemen Produk (CRUD) | ✅ |
| Manajemen Servis + riwayat | ✅ |
| Manajemen Promo (Owner) | ✅ |
| Akun Pegawai (Owner) | ⏳ |
| Laporan Penjualan / Analytics (Owner) | ⏳ |
| Daftar Transaksi | ⏳ |
| Publik: Katalog, Detail, Cek Servis, Cek Nota, About, 404 | ⏳ |

Kualitas: 21 feature test lulus, Larastan level 5 = 0 error.

## B. Alur kerja harian (WAJIB dikuasai)
```bash
cd ~/Desktop/RPL/redline

# 1. Nyalakan (macOS: colima start dulu bila Docker mati)
docker compose up -d

# 2. Buka
#    Aplikasi   → http://localhost:8080   (login: owner / rijal, pass: password)
#    phpMyAdmin → http://localhost:8082   (user redline / redline_secret, DB: redline)

# 3. Setelah mengubah kode:
docker compose exec app php artisan test          # jalankan test
./vendor/bin/phpstan analyse                       # analisis statis (butuh PHP host)
npm run build                                       # bila ubah CSS/JS

# 4. Simpan pekerjaan (git)
git add -A
git commit -m "pesan singkat"

# 5. Matikan bila selesai (data DB tetap aman)
docker compose down
```

## C. Sisa fitur — urutan yang disarankan
Semua mengikuti **pola yang sama** (lihat fitur Produk/Promo sebagai contoh):
`Migration(ada) → FormRequest → Service(bila perlu) → Controller → Route → View → Test`.

### 1. Akun Pegawai (Owner) — paling mudah, mirip Promo
- FormRequest: nama, username unik, email unik, role (enum), password (bcrypt via cast), masih_bekerja.
- Controller resource + view (tabel + form). Route di grup `owner`.
- Keamanan: Owner tidak boleh menonaktifkan/menghapus dirinya sendiri.

### 2. Daftar Transaksi — melengkapi link "View All" di dashboard
- Controller index: `Transaksi::with('items','pegawai')->latest()->paginate()`.
- Filter jenis (Produk/Servis) & tanggal, cari kode_nota. Tombol lihat/unduh nota (route pos.nota sudah ada).

### 3. Laporan Penjualan / Analytics (Owner)
- Agregasi read-only: total per periode (harian/bulanan), produk terlaris, pendapatan per kategori.
- Grafik: cukup CSS bar (seperti dashboard) atau Chart.js. Tombol "Cetak Laporan" → PDF (dompdf).

### 4. Zona publik (task terbesar)
- **Katalog**: filter kategori & harga, kartu produk. Data: `Produk::where('show_katalog', true)`.
- **Detail Produk**: spesifikasi + tombol "Order via WhatsApp" → `https://wa.me/{nomor}?text=...` (nomor di `config/redline.php`).
- **Cek Status Servis**: input nomor resi → tampilkan stepper + riwayat (data `Service` by `nomor_resi`). Sudah ada route placeholder `cek.servis`.
- **Cek Nota Transaksi**: input kode → tampilkan detail nota (data `Transaksi` by `kode_nota`).
- **About Us** & **404**: statis.

### Keputusan: menu "Customers" — SUDAH DIHAPUS
SRS menyatakan manajemen data pelanggan **di luar cakupan**. Menu Customers telah dihapus
dari sidebar & route. Data pembeli tetap tersimpan menempel di `transaksi` (nama/nomor HP)
dan `service` (nama/nomor HP customer), sesuai ERD.

## D. Sebelum deploy (checklist keamanan & produksi)
- [ ] `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru & rahasia.
- [ ] Password DB kuat (ganti `redline_secret` / `root_secret`).
- [ ] HTTPS aktif → set `SESSION_SECURE_COOKIE=true`.
- [ ] `docker compose exec app php artisan config:cache route:cache view:cache`.
- [ ] `composer audit` && `npm audit` → bersih.
- [ ] `php artisan test` → semua hijau.
- [ ] Backup DB terjadwal (SRS: harian, retensi ≥30 hari) — mis. `mysqldump` via cron.
- [ ] Jangan expose port MySQL (3307) & phpMyAdmin (8082) ke publik — hanya web (8080/443).

## E. Push ke GitHub (opsional, direkomendasikan)
```bash
# 1. Buat repo KOSONG di github.com (tanpa README)
# 2. Sambungkan & push:
git remote add origin https://github.com/<user>/<repo>.git
git push -u origin main
```

## F. Deploy (gambaran)
- **Paling mudah**: VPS (mis. DigitalOcean/Contabo) + Docker → `git clone`, isi `.env`, `docker compose up -d --build`, pasang domain + SSL (Caddy/Nginx + Let's Encrypt).
- **Shared hosting/Laragon** (sesuai SRS): upload kode, `composer install --no-dev`, set `.env` MySQL hosting, `php artisan migrate --force`, arahkan document root ke `/public`.

## G. Cara tercepat meneruskan
Minta bantuan per fitur, contoh: *"buat Akun Pegawai"* atau *"buat halaman Cek Servis publik"*.
Setiap fitur akan datang lengkap dengan view, test, dan verifikasi — lalu tinggal di-commit.

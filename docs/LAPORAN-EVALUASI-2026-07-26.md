# Laporan Evaluasi Proyek — SIRC "Redline Komputer"

**Tanggal:** 26 Juli 2026 · **Evaluator:** Senior Engineering Review · **Basis:** kode di working tree (pasca redesign & pemisahan portal subdomain)

---

## 1. Ringkasan Eksekutif

SIRC (Sistem Informasi Redline Komputer) berada dalam kondisi **matang dan hampir siap produksi**. Seluruh fitur fungsional SRS telah terimplementasi (POS + nota PDF, produk, servis, promo, analytics, akun pegawai, seluruh zona publik), ditopang arsitektur MVC + Service Layer yang disiplin, 57 feature test hijau, analisis statis Larastan bersih, dan audit dependensi tanpa CVE.

Dua pekerjaan besar terakhir — **pemisahan portal per subdomain** (publik / karyawan / admin dengan sesi terisolasi) dan **redesign total design system "Instrument Panel"** — terverifikasi berfungsi end-to-end di browser pada ketiga host.

Evaluasi menemukan **1 bug fungsional prioritas tinggi** (tombol "Unduh PDF Nota" di halaman publik menunjuk route internal → 404 bagi customer), **1 masalah kebersihan repo** (file database SQLite liar `redline` ikut ter-commit), serta sejumlah perbaikan kualitas non-blocking (logika impor CSV di controller, query di layout, PDF belum se-branding, CSP masih memakai `unsafe-eval`). Tidak ditemukan kerentanan keamanan kritis. Rekomendasi utama: amankan pekerjaan ke git, perbaiki bug nota publik, lalu jalankan roadmap prioritas di §8.

**Skor keseluruhan: 8,3 / 10** — layak lanjut ke persiapan deploy setelah item P0–P1 dibereskan.

---

## 2. Kondisi Proyek Saat Ini

### 2.1 Identitas & Stack
| Aspek | Detail |
|---|---|
| Produk | Platform POS, servis/reparasi, stok + situs publik customer |
| Stack | Laravel 13 (PHP 8.3), Blade, Bootstrap 5 + design system kustom, Alpine.js, MySQL 8, Vite, dompdf |
| Infrastruktur dev | Docker Compose (PHP-FPM, Nginx, MySQL, Adminer, phpMyAdmin), colima di macOS |
| Ukuran | ±2.780 baris PHP di `app/`, 36 view Blade, 38 definisi route, 9 entitas Eloquent, 6 enum |
| Kualitas terukur | 57 test / 189 assertion hijau · Larastan level 5 = 0 error · `composer audit` & `npm audit` bersih |

### 2.2 Arsitektur Portal (baru)
| Portal | Host lokal | Isi | Login |
|---|---|---|---|
| Publik | `localhost:8080` | Landing, katalog, detail, cek servis, cek nota | — (login sengaja 404) |
| Karyawan | `karyawan.localhost:8080` | Dashboard, POS, produk, servis, transaksi | Role `Karyawan` |
| Admin | `admin.localhost:8080` | Semua fitur + analytics, promo, pegawai, void/export | Role `Owner` |

Ditopang `App\Support\Portal` (peta host→portal), middleware `EnsurePortal` (terdaftar di priority list sebelum `Authenticate`), syarat `role` pada `Auth::attempt`, dan cookie sesi host-only.

### 2.3 Design System v2 "Instrument Panel"
Tipografi Barlow Condensed / Barlow / IBM Plex Mono (self-hosted, subset latin, ±300 KB); motif tick tachometer & stripe livery; zona publik gelap carbon, portal kerja terang; identitas sidebar per portal. Motion: View Transitions API + reveal-on-scroll + guard anti-macet.

### 2.4 Status Pekerjaan
Seluruh perubahan (±44 file berubah, +1.209/−598 baris, +14 file font, 4 file baru) **masih di working tree, belum di-commit**.

---

## 3. Hasil Evaluasi

| Dimensi | Nilai | Catatan singkat |
|---|---|---|
| Arsitektur & struktur kode | 8,5/10 | MVC + Service konsisten; strict types, enum, DTO, `final`. Deviasinya: impor CSV di controller, query di layout. |
| Keamanan | 8,5/10 | Pertahanan berlapis lengkap (lihat §5). Sisa: `unsafe-eval` CSP (Alpine), upload foto di webroot (risiko rendah, sudah tervalidasi mime), belum ada 2FA. |
| UI/UX | 9/10 | Identitas kuat & konsisten tiga zona; responsif; reduced-motion dihormati. PDF belum se-branding; audit aksesibilitas formal belum dilakukan. |
| Testing | 7,5/10 | 57 test fungsional + 6 skenario pemisahan portal. Belum ada: test bug nota publik (regresi), test upload foto, E2E browser otomatis. |
| Dokumentasi | 9/10 | 8 dokumen hidup + changelog rapi; setup, keamanan, arsitektur, deploy checklist mutakhir. |
| DevOps & operasional | 6,5/10 | CI ada (test + phpstan) tapi tanpa build front-end & tanpa `--memory-limit` phpstan; belum ada backup otomatis (wajib SRS), monitoring, atau prosedur deploy teruji. |

---

## 4. Temuan dan Permasalahan

| # | Severity | Temuan | Bukti / Lokasi | Dampak |
|---|---|---|---|---|
| T1 | **Tinggi** | Tombol **"Unduh PDF Nota"** di halaman publik Cek Nota memakai `route('pos.nota')` yang kini berada di zona internal → customer di host publik mendapat **404** | `resources/views/public/cek_nota.blade.php:108` | Fitur publik mati; janji "unduh nota" tidak terpenuhi. (Sebelum pemisahan portal pun link ini sudah salah — dulu redirect ke login.) |
| T2 | **Tinggi** | File **`redline` (SQLite DB, 180 KB) ter-commit di root repo** — artefak test yang salah konfigurasi | `git ls-files` menampilkannya; `file redline` = SQLite 3 | Kebersihan repo, membingungkan, berpotensi memuat data uji; harus dicabut dari git. |
| T3 | Sedang | **Logika bisnis impor CSV berada di controller** (±100 baris parsing/upsert di `ProdukController`), menyimpang dari arsitektur "controller tipis, logika di Service" yang dinyatakan sendiri | `app/Http/Controllers/Internal/ProdukController.php` (241 baris, terbesar) | Sulit diuji terisolasi; duplikasi aturan validasi. |
| T4 | Sedang | **Query di layer view**: layout internal menjalankan query servis aktif untuk dropdown pencarian pada **setiap** page-load, dari blok `@php` di Blade | `resources/views/components/layouts/app.blade.php` | Arsitektur (logic in view) + beban kecil per request; tak terasa di skala toko, tapi pola buruk. |
| T5 | Sedang | **PDF nota & laporan belum diselaraskan** dengan design system v2 (masih palet lama `#0b1c30`/`#c1272c`, DejaVu Sans) | `resources/views/pdf/nota.blade.php`, `internal/analytics/pdf.blade.php` | Inkonsistensi brand pada artefak yang justru dibawa pulang customer. |
| T6 | Sedang | **CSP masih mengizinkan `unsafe-eval`** (kebutuhan Alpine.js build standar) dan `unsafe-inline` untuk style | `app/Http/Middleware/SecurityHeaders.php` | Melemahkan sebagian nilai CSP; ada jalur mitigasi (Alpine CSP build). |
| T7 | Sedang | **CI belum memadai**: phpstan dijalankan tanpa `--memory-limit` (lokal crash di 128 MB), tidak ada `npm run build`, tidak ada `composer audit`/`npm audit` | `.github/workflows/ci.yml` | Regresi build front-end / OOM phpstan bisa lolos tanpa terdeteksi. |
| T8 | Sedang | **Backup DB otomatis belum ada** padahal SRS mensyaratkan backup harian retensi ≥30 hari | Checklist `docs/06` masih kosong | Kewajiban SRS belum terpenuhi; risiko kehilangan data. |
| T9 | Rendah | Foto produk disimpan di **disk `public` (webroot)**; dokumen keamanan menargetkan "di luar webroot". Mime & ukuran sudah divalidasi; katalog publik tidak lagi menampilkan foto | `app/Services/ProductService.php:60` | Risiko rendah (file polyglot terlayani dari domain sendiri; dimitigasi `nosniff` + CSP). |
| T10 | Rendah | `function rl_rp()` global dideklarasikan di view dashboard — render ganda dalam satu request akan fatal redeclare; pola tidak lazim | `resources/views/internal/dashboard.blade.php:3` | Rapuh; sebaiknya helper/`Number::currency`. |
| T11 | Rendah | **View mati**: `welcome.blade.php`, `internal/soon.blade.php` tidak direferensikan route mana pun; `.DS_Store` belum di-ignore | grep route/usage | Sampah kode. |
| T12 | Rendah | `KodeGenerator` cek-lalu-insert tanpa retry saat tabrakan; unique index menyelamatkan, tapi checkout paralel yang kebetulan bentrok akan error, bukan mencoba ulang | `app/Services/KodeGenerator.php` | Probabilitas sangat kecil; perbaikan murah (retry di catch). |
| T13 | Rendah | `storage:link` tidak dibuat otomatis di entrypoint Docker — clone segar akan 404 untuk foto produk sampai dijalankan manual | `docker/` entrypoint | Gesekan onboarding/deploy. |
| T14 | Info | `TrustHosts` nonaktif di `APP_ENV=local` dan saat test — **by design Laravel** (kenyamanan dev); aktif otomatis di produksi | didokumentasikan di `docs/04` | Perlu dipastikan saat smoke test produksi. |
| T15 | Info | Owner tidak bisa masuk portal karyawan (1 role ↔ 1 portal, keputusan sadar). Bila kelak owner perlu mengoperasikan POS dari portal karyawan, perlu keputusan produk | `EnsurePortal` | Batasan disengaja, bukan cacat. |

---

## 5. Kelebihan Proyek

1. **Keamanan berlapis yang menyeluruh** — pemisahan permukaan serangan per subdomain dengan sesi host-only; role↔portal ditegakkan saat login *dan* tiap request; zona internal disembunyikan (404); pesan galat login generik anti-enumerasi; rate-limit per portal+username+IP + log kegagalan; `TrustHosts`; CSP ber-nonce; noindex + robots dinamis; font self-hosted (nol pihak ketiga); audit dependensi bersih.
2. **Arsitektur disiplin dan modern** — MVC + Service Layer, DTO `readonly`, enum untuk semua nilai berhingga, `strict_types` menyeluruh, FormRequest terpusat, transaksi DB + `lockForUpdate` pada checkout (harga dihitung server-side).
3. **Identitas visual kuat dan bermakna** — design system diturunkan dari makna nama brand (redline tachometer), konsisten dari landing publik sampai stepper servis; bukan template generik.
4. **Kualitas terverifikasi otomatis** — 57 test (termasuk 6 skenario keamanan lintas portal), Larastan bersih, CI di GitHub Actions.
5. **Dokumentasi hidup** — 8 dokumen + changelog yang benar-benar mengikuti kode, termasuk checklist deploy dan panduan subdomain.
6. **Detail ketahanan front-end yang jarang diperhatikan** — guard view-transition macet, reveal anti-skip, `prefers-reduced-motion`, empty state, view 404 kamuflase di semua host.
7. **Developer experience baik** — Docker satu perintah, seeder demo kaya, `*.localhost` tanpa konfigurasi DNS.

---

## 6. Kekurangan Proyek

1. **Satu alur publik cacat** (T1 — nota PDF customer 404) — satu-satunya cacat fungsional yang terlihat pengguna akhir.
2. **Kesenjangan operasional produksi** — belum ada backup otomatis (kewajiban SRS), monitoring/alerting, prosedur deploy teruji, atau smoke test pasca-deploy (T7, T8, T13).
3. **Deviasi arsitektur kecil yang menumpuk** — impor CSV di controller, query di layout, helper global di view (T3, T4, T10) — tidak berbahaya hari ini, tapi mengikis standar yang proyek ini sendiri tetapkan.
4. **Konsistensi brand belum 100%** — artefak PDF masih desain lama (T5).
5. **Higienis repo** — file DB liar ter-commit, view mati, `.DS_Store` (T2, T11); seluruh pekerjaan besar belum di-commit.
6. **Perimeter keamanan masih bisa dinaikkan** — `unsafe-eval` di CSP, upload di webroot, belum ada 2FA/opsi ganti password mandiri, kebijakan password minimal 8 tanpa kompleksitas (T6, T9).
7. **Cakupan test belum menyentuh jalur samping** — upload foto, impor CSV kasus batas, alur nota publik, E2E browser.

---

## 7. Rekomendasi Perbaikan

**Segera (menjaga hasil kerja):**
1. **Commit seluruh working tree sekarang** dalam 2–3 commit logis (arsitektur portal · design system+views · docs), sebelum perubahan lain menyentuhnya.
2. **Cabut `redline` dari git** (`git rm --cached redline`), tambahkan `redline`, `*.sqlite`, `.DS_Store` ke `.gitignore` (T2, T11).
3. **Perbaiki T1** dengan salah satu dari: (a) route publik baru `GET /nota/{kode_nota}/pdf` ber-`throttle` + lookup by kode (bukan id) yang merender PDF sama, atau (b) sembunyikan tombol di host publik. Rekomendasi: (a) — nilainya nyata bagi customer; tambahkan feature test regresinya.

**Struktural (minggu berjalan):**
4. Ekstrak impor CSV ke `ProductService`/`ProdukImportService` + unit test kasus batas (kolom hilang, duplikat SKU, angka salah) (T3).
5. Pindahkan query pencarian topbar ke View Composer atau endpoint ringan (T4); ganti `rl_rp()` dengan helper terpusat/`Illuminate\Support\Number` (T10).
6. Perkuat CI: `--memory-limit=1G` untuk phpstan, tambah job `npm ci && npm run build`, tambah `composer audit` + `npm audit` (T7).
7. Selaraskan PDF nota & laporan dengan palet/tipografi v2 — dompdf cukup diberi palet baru + hierarki tipografis serupa (T5).

**Pengerasan (sebelum deploy):**
8. Backup MySQL harian (cron `mysqldump` + rotasi ≥30 hari) dan uji restore — kewajiban SRS (T8).
9. `storage:link` di entrypoint Docker (T13); retry-on-collision di `KodeGenerator` (T12).
10. Ganti Alpine ke **CSP build** untuk menghapus `unsafe-eval`; evaluasi penghapusan `unsafe-inline` style bertahap (T6).
11. Naikkan kebijakan password (`Password::min(8)->letters()->numbers()` + `uncompromised()` bila online), pertimbangkan halaman ganti-password mandiri; 2FA TOTP untuk Owner sebagai fase berikutnya (T6/§8).

---

## 8. Roadmap Pengembangan (berdasarkan prioritas)

**P0 — Hari ini (stabilisasi):**
- Commit pekerjaan (3 commit logis) · bersihkan `redline`/`.DS_Store` dari git · hapus view mati.
- Fix bug nota publik (T1) + test regresi.

**P1 — Minggu ini (kualitas inti):**
- Refactor impor CSV ke Service + test · View Composer topbar · helper mata uang.
- CI diperkuat (phpstan memory, build front-end, audits).
- PDF nota/laporan disesuaikan design system v2.
- `storage:link` otomatis + retry KodeGenerator.

**P2 — Sebelum go-live (operasional & keamanan produksi):**
- Backup harian + uji restore (SRS) · prosedur deploy VPS terdokumentasi & dilatih (DNS subdomain, TLS wildcard, `server_name` eksplisit, `config:cache`).
- Smoke test produksi: TrustHosts aktif, HSTS, `SESSION_SECURE_COOKIE=true`, ketiga host, robots.
- Alpine CSP build (hapus `unsafe-eval`) · kebijakan password diperkuat.
- Audit aksesibilitas ringan (fokus: kontras zona gelap, skip-link, aria stepper) + Lighthouse budget.

**P3 — Pasca go-live (fitur bernilai berikutnya, urut usulan):**
1. **Notifikasi status servis via WhatsApp** (template pesan per perubahan status) — menyambung fitur cek-servis yang sudah kuat.
2. **2FA TOTP untuk Owner** + manajemen sesi aktif (logout perangkat lain) — melengkapi arsitektur portal.
3. **Cetak thermal / ESC-POS** untuk nota kasir (POS saat ini berbasis PDF A4).
4. **Stok opname & riwayat mutasi stok** (audit trail pergerakan barang).
5. **Laporan laba** memanfaatkan `harga_modal` yang sudah ada di skema (margin per produk/periode).
6. **PWA untuk POS** (instalable, cache aset; evaluasi offline-first belakangan).
7. Ekspor akuntansi (CSV/Excel periode) & arsip nota email otomatis.

**Eksplisit di luar cakupan (sesuai SRS):** manajemen data pelanggan (CRM), pembayaran online, multi-cabang.

---

## 9. Kesimpulan

Proyek SIRC telah melewati fase implementasi fitur dan kini berdiri di atas fondasi yang kuat: arsitektur bersih, keamanan berlapis dengan pemisahan portal subdomain yang terverifikasi, identitas visual yang khas dan konsisten, serta jaring pengaman kualitas otomatis (57 test, Larastan, CI, audit dependensi bersih).

Sisa pekerjaan bukan lagi membangun, melainkan **merapikan dan mengoperasikan**: satu bug publik yang harus ditutup (T1), kebersihan repo (T2), beberapa refactor kecil agar kode terus setia pada standarnya sendiri, dan kesenjangan operasional produksi (backup, CI yang lebih ketat, prosedur deploy). Dengan menyelesaikan P0–P1 dalam beberapa hari kerja dan P2 sebelum go-live, sistem ini siap dioperasikan sebagai platform harian Toko Redline Komputer dengan risiko teknis yang rendah dan jalur pengembangan (P3) yang jelas nilainya bagi bisnis.

---
*Lampiran metode: evaluasi dilakukan terhadap kode aktual (bukan dokumen saja) — pembacaan sumber, `php artisan test`, Larastan, `composer/npm audit`, inspeksi HTTP langsung ke tiga host (status, header, robots), dan verifikasi visual browser desktop+mobile pada 12+ halaman.*

# 09 — Status Proyek & Titik Lanjut

> **Dokumen serah-terima.** Baca file ini untuk melanjutkan pengembangan dari titik terakhir —
> berisi semua yang SUDAH dikerjakan, yang BELUM, keputusan desain yang wajib dipertahankan,
> dan cara memulai lagi.
>
> Pembaruan terakhir: **27 Juli 2026** · commit `612ca12` · branch `main`
> (`origin` github.com/yhnzzzwp/redlinecomp) · **114 test lulus** · Larastan level 5 = 0 error.

---

## 1. Gambaran Proyek Saat Ini

SIRC (Sistem Informasi Redline Komputer): POS + servis + stok + situs publik customer.
**Laravel 13 · PHP 8.3 (Docker) / 8.5 (host) · Blade · Bootstrap 5 + design system kustom ·
Alpine (internal saja) · MySQL 8 · PhpSpreadsheet 5 · dompdf.**

### Arsitektur tiga portal (subdomain, sesi terisolasi per host)
| Portal | URL lokal | Isi | Login |
|---|---|---|---|
| Publik | `http://localhost:8080` | Beranda = hero + **katalog langsung**, detail produk, lacak servis, tentang kami | tidak ada — `/login` sengaja **404** |
| Karyawan | `http://karyawan.localhost:8080` | Dashboard, POS, Produk, **Stok** (opname+mutasi), Transaksi, Servis | role `Karyawan` |
| Admin | `http://admin.localhost:8080` | Semua fitur karyawan **+** Analytics (laba + jurnal), Promo, Akun Pegawai, void/export | role `Owner` |

Kredensial demo: `owner`/`password` (portal admin) · `rijal`/`password` (portal karyawan).

### Alur kerja harian
```bash
colima start && docker compose up -d      # nyalakan (macOS)
php artisan test                          # 114 test — harus selalu hijau
./vendor/bin/phpstan analyse --memory-limit=1G
npm run build                             # bila menyentuh CSS/JS
docker compose exec -T app php artisan migrate --force   # bila ada migrasi baru
git add -A && git commit && git push origin main
```

---

## 2. SUDAH Dikerjakan (kronologis, per commit)

Total perubahan sejak `32ba211`: **±6.307 baris tambah / 1.560 hapus** dalam 33 commit.

| Commit | Isi |
|---|---|
| `7b9c942` | **Bersih-bersih repo**: hapus DB SQLite liar `redline` dari git, view mati, `.gitignore` diperketat |
| `fcf72e6` | **Pemisahan portal per subdomain**: enum `App\Support\Portal`, middleware `EnsurePortal` (prioritas sebelum `Authenticate`), login per portal dengan syarat role + pesan galat generik, sesi host-only, `TrustHosts`, noindex + robots dinamis, `PortalSeparationTest` |
| `acaf69c` | **Design system v2 "Instrument Panel"**: Barlow Condensed + Barlow + IBM Plex Mono (self-host `public/fonts`), motif tick tachometer & stripe 18°, rombak seluruh view + login split-panel per portal |
| `116c528` | Dokumentasi arsitektur/keamanan/setup + **laporan evaluasi lengkap** (`docs/LAPORAN-EVALUASI-2026-07-26.md` — sumber roadmap P0–P3) |
| `e7e929e` | Fix bug nota publik 404 (kini fitur cek nota sudah dihapus seluruhnya — lihat `910d61b`) |
| `910d61b` | **Tema publik TERANG** (carbon hanya di hero & panel login), **beranda = katalog langsung** (filter+grid+pagination, `/catalogue` → redirect beranda), navbar publik 3 menu (Beranda·Lacak Servis·Tentang Kami), **fitur Cek Nota dihapus**, visi-misi profesional + ikon SVG (emoji dibuang), QR pihak-ketiga di PDF dihapus |
| `7a52e31` | **Impor–ekspor produk Excel menggantikan CSV**: `ProdukExcelService` (PhpSpreadsheet), header alias + format "Rp x.xxx", upsert ber-SKU, **all-or-nothing** + galat per baris, template + ekspor .xlsx (dropdown kategori, anti formula-injection), CSV ditolak |
| `c855890` | **Sidebar admin ikut terang** (varian carbon dihapus) · **seluruh aplikasi bebas gambar** (drop kolom `foto_produk`+`foto_promo`, upload & thumbnail dihapus) · **responsif mobile menyeluruh** (input 16px anti-zoom iOS, filter katalog kolaps, tabel scroll dalam kartu, hero stats vertikal) |
| `ddaa801` | **P1 kualitas inti**: `Uang::rupiah()` terpusat (9 view), `TopbarSearchComposer` (query keluar dari Blade), `CobaUlang::unik()` (retry tabrakan kode nota/resi), PDF nota+laporan palet v2, CI diperkuat (composer audit, phpstan memory, job frontend npm audit+build) |
| `4367840` | **P2 go-live**: `scripts/backup-db.sh` (harian, retensi 30 hari, **restore teruji** via `scripts/restore-db.sh`), `docs/08-deploy-produksi.md`, `scripts/smoke-produksi.sh` (lulus 11/11 lokal), **CSP publik tanpa `unsafe-eval`** (nav+filter publik = vanilla JS), password pegawai wajib huruf+angka, skip-link + aria stepper |
| `605d86b` | **P3.1–P3.4**: tombol **Kirim Update WA** servis (`App\Support\Wa`, wa.me tanpa API), **2FA TOTP Owner** (halaman Keamanan, tantangan login, 6 kode pemulihan hash, secret terenkripsi), **struk thermal 80mm** (`/pos/struk/{trx}`), **Laba per Produk** di Analytics+PDF |
| `d69c3cf` | **P3.5 Stok opname + mutasi stok**: tabel `mutasi_stok`, `StokService` satu pintu, tercatat dari 5 titik (POS/void/edit/impor/opname), halaman Opname (selisih live) + Riwayat Mutasi (filter) + tombol Riwayat per produk |
| `c3ca92d` | **P3.6 PWA POS**: manifest via route `portal:internal` (404 dari publik, nama per portal, `start_url` `/pos`), ikon RL digenerate lokal (`scripts/buat-ikon-pwa.php`, GD + font self-host), meta pemasangan di layout internal — **service worker sengaja belum** (CSP ketat, evaluasi belakangan) |
| `d184761` | fix: hint password form pegawai menyebut aturan min 8 huruf+angka |
| `4668b32` | **Poles PWA hasil review adversarial**: link manifest di halaman login (install dari perangkat baru), meta modern `mobile-web-app-capable`, smoke cek manifest karyawan+admin+publik (14 cek lokal), test dimensi PNG vs deklarasi `sizes` |
| `cbcbdcd` | **P3.7 Ekspor Jurnal Akuntansi** (.xlsx, Owner): jurnal umum double-entry per transaksi (Kas/Bank/QRIS · Diskon · Pendapatan Produk/Servis · HPP↔Persediaan), 3 sheet (Jurnal+TOTAL, Rekap Akun, Info), bagan akun konfigurabel di `config/redline.php` |
| `2d029b0` | **Integritas servis & jurnal (hasil review)**: POS menagih `totalBiaya()` (jasa+part — fix kurang tagih), mutasi part servis via `StokService` (tipe `Part Servis`), snapshot `harga_modal` (HPP tak berubah retroaktif), HPP part dijurnal, `lazy()` anti N+1, cap periode 1 tahun |
| `c2b1db0` | **Fitur 2FA TOTP Owner DIHAPUS** (keputusan Owner): halaman/menu Keamanan, tantangan login, kolom DB (drop), dependensi google2fa — login Owner kembali password saja |
| `edf0dfb` | **UI mobile**: chip Kategori POS digulir ke samping (bukan wrap), menu publik jadi drawer meluncur dari kanan (overlay+X+Escape, vanilla JS patuh CSP; fix `backdrop-filter` containing-block via `::before`) |
| `186db79`+`af661cb` | **Service worker offline shell PWA POS**: `/build` cache-first, `/fonts`+`/icons` stale-while-revalidate, navigasi selalu jaringan → fallback `offline.html`; non-GET & HTML terautentikasi TIDAK PERNAH disentuh (**checkout offline sengaja tak didukung** — dijaga test); registrasi ter-gate link manifest; CSP `worker-src` internal `'self'` / publik `'none'` |
| `ccca2f6` | **WA semi-otomatis saat ganti status** (keputusan Owner, tetap wa.me tanpa API): centang "kirim WA" default aktif → WhatsApp terbuka otomatis berisi pesan status baru, banner tombol = fallback popup-diblokir; template disatukan ke `App\Support\Wa` (estimasi biaya kini hanya di status Selesai — sadar) |
| `9f59a80` | **Manajemen sesi aktif**: halaman Sesi Aktif (perangkat login milik sendiri: label UA, IP, terakhir aktif, badge "Sesi ini"), Keluarkan per perangkat + Keluarkan Semua |
| `b4c354a` | Riwayat status saat servis diambil via POS (timeline SRS §2.5 tidak bolong) + konfirmasi destruktif dipindah dari `onsubmit` inline (diblokir CSP → dialog tak pernah muncul) ke Alpine |
| `c6beb85` | **Fix XSS/regresi `@js()`**: `Js::from()` selalu berkutip tunggal → di atribut kutip-tunggal atribut PUTUS (tombol Hapus mati + nama produk bisa jadi atribut `x-init` yang dieksekusi Alpine). Semua pindah ke `x-data="{ nama: @js(...) }"`, dikunci `KonfirmasiDestruktifTest` |
| `9122b89` | **Pengerasan sesi**: rotasi `remember_token` saat mengeluarkan perangkat (tanpa ini cookie "Ingat perangkat" menghidupkan sesi lagi), `AuthenticateSession` aktif (ganti password mengakhiri sesi lain), middleware `PastikanMasihBekerja` (pegawai nonaktif langsung ter-logout) |
| `e331ea7` | **Fix POS servis**: layar POS mengirim `biaya_service` saja padahal server menagih `totalBiaya()` (jasa+part) — kasir bayar sesuai layar lalu ditolak "pembayaran kurang" (150rb vs 550rb). Selain itu servis berstatus **Selesai justru disembunyikan** dari POS; kini hanya *Sudah Diambil* yang dikecualikan, yang *Selesai* di urutan atas, kartu servis menampilkan status (bukan "Stok: 1") |
| `612ca12` | **Owner mengeluarkan sesi pegawai lain**: kolom Perangkat + tombol Keluarkan Sesi di Akun Pegawai (hapus semua sesi pegawai itu + rotasi `remember_token`; tetap aktif saat 0 perangkat karena cookie recaller hidup lebih lama daripada sesi) |

Kualitas terkunci otomatis: **114 test / 465 assertion**, Larastan bersih, CI GitHub Actions
(test+phpstan+audit PHP · npm audit+build), `composer audit` & `npm audit` bersih.

---

## 3. BELUM Dikerjakan (titik lanjut berikutnya)

### Sisa roadmap P3
**Tidak ada — roadmap P3 selesai seluruhnya.** PWA POS ✅ (`c3ca92d`+`4668b32`),
Ekspor Jurnal Akuntansi ✅ (`cbcbdcd`+`2d029b0`; bagan akun tinggal disesuaikan
akuntan lewat `config/redline.php` bagian `akun` bila perlu).

### Ide pasca-P3 (dari laporan evaluasi, belum diprioritaskan)
- Jurnal balik otomatis untuk Void/Refund lintas periode di Ekspor Jurnal Akuntansi
  (sekarang: Void dikecualikan + peringatan di sheet Info; koreksi lintas periode manual).
- Audit aksesibilitas formal + Lighthouse budget.
- Alpine CSP build untuk portal internal (publik sudah tanpa `unsafe-eval`; internal masih pakai
  karena POS bergantung ekspresi inline Alpine — keputusan sadar, lihat §4).

### Catatan kecil yang diketahui (bukan bug)
- Produk hasil impor Excel milik pemakai belum berisi `harga_modal` → kolom Laba tampil 100%.
  Isi via edit produk / ekspor-ubah-impor agar laporan laba bermakna.
- Pengambilan servis via POS men-set status `SudahDiambil` langsung TANPA menawarkan WA —
  **by design** (customer sedang berdiri di kasir); tombol manual "Kirim Update WA" tetap ada.
- POS menampilkan **semua servis yang belum diambil**, termasuk yang masih dikerjakan
  (kartunya diberi label "Belum selesai (…)"). Disengaja: customer bisa membayar & membawa
  pulang unit sebelum rampung; `PosService` memang tanpa guard transisi di jalur ini.
- `TrustHosts` nonaktif di `APP_ENV=local` & saat test (perilaku bawaan Laravel) — aktif di produksi.
- Opsi hardening belum diterapkan: `/sw.js`, `/offline.html`, `/icons/*` statis ikut tersaji di host
  publik (isinya generik, registrasi SW publik sudah diblok CSP `worker-src 'none'`); bila ingin nol
  jejak, blok path itu untuk host publik di config webserver produksi.
- Belum go-live: ikuti `docs/08-deploy-produksi.md` + `scripts/smoke-produksi.sh` saat domain/VPS siap.

---

## 4. Keputusan Desain yang WAJIB Dipertahankan

1. **1 role ↔ 1 portal**: Owner hanya di `admin.*`, Karyawan hanya di `karyawan.*`; login di
   portal salah = pesan generik (anti enumerasi). Zona internal dari host publik = **404**, bukan redirect.
2. **Nol pihak ketiga di runtime**: font self-host, tanpa CDN, WA via wa.me (bukan API).
   Jangan menambah dependensi eksternal tanpa alasan kuat.
3. **CSP publik ketat tanpa `unsafe-eval`** → semua interaksi zona publik HARUS vanilla JS
   (lihat `initPublik()` di `resources/js/app.js`); Alpine hanya untuk portal internal.
4. **Tanpa gambar**: aplikasi tidak menampilkan/mengunggah gambar (keputusan Owner; kolom foto
   sudah di-drop). Jangan menambahkan kembali tanpa permintaan eksplisit.
5. **Excel menggantikan CSV** untuk produk; impor **all-or-nothing** dengan galat per baris.
6. **Semua pergerakan stok lewat `StokService::catat()`** — fitur baru yang menyentuh
   `jumlah_produk` wajib ikut mencatat mutasi.
7. **Uang diformat lewat `App\Support\Uang::rupiah()`** — jangan buat formatter baru.
8. Konvensi kode: `declare(strict_types=1)`, kelas `final`, enum untuk nilai berhingga,
   controller tipis + Service, FormRequest untuk validasi, teks UI bahasa Indonesia.
9. Tema: terang di seluruh aplikasi; carbon gelap hanya hero beranda + panel kiri login.

---

## 5. Peta File Penting

```
app/Support/            Portal (host→portal) · Uang · Wa · CobaUlang
app/Http/Middleware/    EnsurePortal (pemisah zona) · SecurityHeaders (CSP per portal) ·
                        PastikanMasihBekerja (pegawai nonaktif langsung keluar)
app/Services/           PosService · ProdukExcelService · StokService · ProductService ·
                        ServiceTicketService · PromoService · KodeGenerator
app/Http/Controllers/   AuthController (login per portal) ·
  Internal/             StokController · PosController (nota+struk) ·
                        PwaController (manifest per portal) · …
resources/css/app.css   Design system v2 (token → komponen rl-* → responsif → motion)
resources/js/app.js     Alpine (internal) + vanilla publik + guard view-transition + reveal
resources/views/
  public/               landing (hero+katalog) · catalogue/show · cek_servis · about · soon
  internal/             dashboard · pos · struk · stok/{opname,mutasi} · …
  auth/                 login (split per portal)
scripts/                backup-db.sh · restore-db.sh · smoke-produksi.sh ·
                        buat-ikon-pwa.php (regenerasi ikon public/icons)
docs/08-deploy-produksi.md   Prosedur go-live lengkap
docs/LAPORAN-EVALUASI-2026-07-26.md   Laporan evaluasi + roadmap asal (P0–P3)
```

---

## 6. Cara Melanjutkan

1. Nyalakan lingkungan (lihat §1), pastikan `php artisan test` hijau **sebelum** mengubah apa pun.
2. Ambil item dari §3, kerjakan dengan pola proyek:
   `Migration → FormRequest → Service → Controller → Route → View → Test`.
3. Setiap fitur selesai: test + phpstan + build → perbarui `CHANGELOG.md` → commit pesan
   berbahasa Indonesia berformat `feat|fix|chore(scope): …` → push.
4. Contoh kalimat untuk memulai sesi AI berikutnya:
   *"Baca docs/09-status-dan-lanjutan.md, lalu kerjakan PWA untuk POS."*

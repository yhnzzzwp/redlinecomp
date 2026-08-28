# Panduan Lengkap Deploy, Konfigurasi Cloudflare Tunnel, dan Koneksi Frontend Vercel

Dokumen ini merangkum seluruh langkah teknis yang telah dikerjakan untuk membangun Backend (REST API), menjalankan container server, membuka akses tunnel publik via Cloudflare Zero Trust, dan menghubungkannya dengan frontend Next.js di Vercel.

---

## 1. Ringkasan Arsitektur Sistem

```
+-------------------------------------------------------------------+
|                        PENGUNJUNG INTERNET                        |
|                                 |                                 |
|            https://redline-testing1.yohaneswp.sbs                |
|                    (Frontend Next.js on Vercel)                   |
+-------------------------------------------------------------------+
                                  |
                                  | fetch API: NEXT_PUBLIC_API_BASE_URL
                                  v
+-------------------------------------------------------------------+
|                     CLOUDFLARE ZERO TRUST TUNNEL                  |
|                 https://api-redline.yohaneswp.sbs                 |
+-------------------------------------------------------------------+
                                  |
                                  | Secure Encrypted Tunnel (cloudflared)
                                  v
+-------------------------------------------------------------------+
|                     KOMPUTER HOST / SERVER LOKAL                  |
|                                                                   |
|   [Docker Network: redline]                                       |
|     - redline-web (Nginx: Port 8000 -> 80)                        |
|     - redline-app (PHP 8.3 FPM + Laravel 13 Backend API)          |
|     - redline-db  (MariaDB 11: Port 3307 -> 3306)                 |
|     - redline-adminer (Adminer DB GUI: Port 8081)                 |
+-------------------------------------------------------------------+
```

---

## 2. Rincian Pekerjaan Backend yang Telah Selesai

1. **Sistem Autentikasi API (Bearer Token):**
   - Dibuat tabel `api_tokens` (`pegawai_id`, `name`, `token` sha256, `abilities`, `expires_at`).
   - Dibuat middleware `EnsureApiAuthenticated` (`auth.api`) & `EnsureApiOwner` (`owner.api`).
   - Endpoint: Login, Logout, Me, Update Profile, Update Password.

2. **Katalog & Kategori Produk:**
   - Endpoint publik: `GET /api/v1/katalog`, `GET /api/v1/katalog/{id}`, `GET /api/v1/kategori`.
   - Endpoint manajemen staf/owner: CRUD lengkap produk dan kategori di `/api/v1/admin/produk` & `/api/v1/admin/kategori`.

3. **Promo & Diskon Otomatis:**
   - Endpoint publik: `GET /api/v1/promo`, `POST /api/v1/promo/cek` (menghitung diskon nominal / persen & batas maksimal secara otomatis).
   - Endpoint manajemen owner: CRUD promo & toggle status aktif di `/api/v1/admin/promos`.

4. **Layanan Servis & Pelacakan Unit:**
   - Endpoint publik: `GET /api/v1/service/cek?resi=...` & `GET /api/v1/perangkat/{kode}` (QR tracking).
   - Endpoint manajemen: Buat tiket servis, update status (otomatis membuat tautan WhatsApp customer), tambah/hapus part sparepart di `/api/v1/admin/services`.

5. **Point of Sale (POS) & Transaksi:**
   - Endpoint kasir: `GET /api/v1/pos/items`, `POST /api/v1/pos/checkout`, `POST /api/v1/pos/sync` (idempotent offline sync), invoice `GET /api/v1/pos/nota/{id}`, dan struk thermal `GET /api/v1/pos/struk/{id}`.
   - Endpoint riwayat transaksi & pembatalan/void di `/api/v1/admin/transaksi`.

6. **Dashboard KPI & Analitik Penjualan:**
   - Ringkasan performa penjualan dan status servis aktif di `GET /api/v1/admin/dashboard`.
   - Laporan analitik tren harian dan metode bayar di `GET /api/v1/admin/analytics`.

7. **Konfigurasi Docker & Database Optimal:**
   - Nginx dikonfigurasi ke port `8000` (`APP_PORT=8000`).
   - Database beralih ke `mariadb:11` dengan healthcheck instan (`healthcheck.sh`).
   - Migrasi dan seeder otomatis dieksekusi saat container pertama kali dijalankan.

---

## 3. Konfigurasi Cloudflare Tunnel (`api-redline.yohaneswp.sbs`)

Agar backend lokal dapat diakses secara publik dan aman via HTTPS:

1. Buka **Cloudflare Zero Trust Dashboard** → **Networks** → **Tunnels** → Pilih tunnel aktif Anda.
2. Tambahkan / Edit **Public Hostname**:
   - **Subdomain**: `api-redline`
   - **Domain**: `yohaneswp.sbs`
   - **Path**: *(dikosongkan)*
   - **Type**: `HTTP`
   - **Service URL**: `http://192.168.0.107:8000` *(atau IP lokal host komputer Anda)*
3. Endpoint publik aktif di: **`https://api-redline.yohaneswp.sbs/api/v1`**

---

## 4. Konfigurasi di Frontend Vercel (`redline-testing1.yohaneswp.sbs`)

1. Buka **Vercel Dashboard** → Pilih project `redline-frontend`.
2. Masuk ke **Settings** → **Environment Variables**.
3. Tambahkan variabel lingkungan:
   - **Key**: `NEXT_PUBLIC_API_BASE_URL`
   - **Value**: `https://api-redline.yohaneswp.sbs/api/v1`
   - **Environment**: Centang **Production**, **Preview**, dan **Development**.
4. Masuk ke menu **Deployments** → Klik titik tiga (`...`) pada deployment terbaru → Klik **Redeploy**.
5. Hasil: Indikator di navbar website berubah menjadi **🟢 Terkoneksi** dan data produk termuat dari database live.

---

## 5. Cara Menjalankan di Komputer / Laptop Baru

Cukup clone repository ini di laptop baru Anda:
```bash
git clone https://github.com/yhnzzzwp/redlinecomp.git
cd redlinecomp

cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

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
                                  | fetch API (server-side): API_BASE_URL
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

Backend dijangkau dari internet lewat Cloudflare Tunnel: cloudflared membuka
koneksi **keluar** ke Cloudflare, jadi tidak perlu IP publik, port forwarding,
maupun IP tetap. Server boleh dinyalakan di WiFi mana pun.

Ada dua susunan. **Opsi A dianjurkan** karena membuat Redline berdiri sendiri:
seluruh konfigurasinya ikut repo ini, sehingga pindah komputer cukup clone +
isi token.

### Opsi A — tunnel milik stack Redline (dianjurkan)

1. Cloudflare Zero Trust → **Networks → Tunnels → Create a tunnel** →
   pilih **Cloudflared** → salin **token connector**-nya.
2. Tempel ke `.env` repo ini:
   ```dotenv
   CLOUDFLARE_TUNNEL_TOKEN=eyJhIjoi...
   TUNNEL_PROTOCOL=http2
   ```
3. Nyalakan connector-nya:
   ```bash
   ./scripts/redline.sh tunnel
   ```
4. Di tunnel tersebut, tambahkan **Public Hostname**:
   - **Subdomain**: `api-redline` · **Domain**: `yohaneswp.sbs` · **Path**: *(kosong)*
   - **Type**: `HTTP`
   - **Service URL**: **`http://web:80`** — nama service nginx di jaringan
     compose ini. Lalu lintasnya tidak pernah keluar dari jaringan Docker.
5. Verifikasi: `./scripts/redline.sh status` → `✔ Publik …` dan
   `✔ Tunnel Cloudflare aktif (redline-tunnel, milik stack ini)`.

### Opsi B — menumpang connector project lain

Susunan yang dipakai sebelumnya: connector milik stack `~/personal-server`
(yang aslinya untuk Nextcloud) sekaligus melayani Redline. Sah saja, hanya
saja konfigurasi infrastruktur Redline jadi berada di luar repo ini.

- **Service URL**: **`http://host.docker.internal:8000`**, dan container
  cloudflared wajib punya pemetaan berikut (sudah disetel di
  `~/personal-server/compose.cloudflare.yml`):
  ```yaml
  extra_hosts:
    - "host.docker.internal:host-gateway"
  ```
- `./scripts/redline.sh status` akan menandai kondisi ini dengan
  `TAPI dikelola compose project lain`.

> ### ⚠️ JANGAN isi Service URL dengan IP LAN (mis. `http://192.168.0.107:8000`)
>
> IP LAN host berubah setiap server dinyalakan di jaringan lain (pindah tempat,
> ganti WiFi, sewa DHCP baru). Ingress tunnel tetap menunjuk IP lama, sehingga
> backend **mati total dari luar** walaupun container-nya sehat — persis
> kejadian yang memunculkan dokumen bagian ini. Gejalanya di log cloudflared:
>
> ```
> ERR Unable to reach the origin service ... dial tcp 192.168.0.107:8000: i/o timeout
>     originService=http://192.168.0.107:8000
> ```
>
> `http://web:80` (Opsi A) memakai DNS jaringan Docker, dan
> `host.docker.internal` (Opsi B) diterjemahkan Docker ke gateway bridge saat
> container start. Dua-duanya tidak terpengaruh IP LAN.

### Transport: http2 vs quic

Bawaan cloudflared (`auto`) memilih QUIC yang butuh **UDP/7844 keluar** — lazim
diblokir di WiFi kantor, kampus, dan hotel. `TUNNEL_PROTOCOL=http2` memakai
TCP/443 yang hampir selalu lolos, dengan biaya performa yang tidak terasa pada
beban sebesar ini. Ganti ke `quic` hanya bila jaringannya memang bersahabat.

### Domain tunnel wajib dipercaya backend

Isi `REDLINE_EXTRA_HOSTS` dengan host publik tersebut (mis.
`api-redline.yohaneswp.sbs`). Di produksi `trustHosts` tidak lagi memakai
wildcard, jadi tanpa pendaftaran ini Host header dari Cloudflare ditolak dan
backend kembali terlihat "mati dari luar" padahal sehat.

---

## 4. Konfigurasi di Frontend Vercel (`redline-testing1.yohaneswp.sbs`)

1. Buka **Vercel Dashboard** → Pilih project `redline-frontend`.
2. Masuk ke **Settings** → **Environment Variables**.
3. Tambahkan variabel lingkungan:
   - **Key**: `API_BASE_URL` — **tanpa** awalan `NEXT_PUBLIC_`. Frontend
     memanggil backend dari sisi server (BFF `/api/backend`), dan kodenya
     membaca `process.env.API_BASE_URL`. Memakai nama `NEXT_PUBLIC_API_BASE_URL`
     membuat variabel itu tidak pernah terbaca, lalu fetch server jatuh ke
     nilai bawaan `http://localhost:8080/api/v1` — yang di Vercel berarti
     backend tidak dapat dihubungi sama sekali.
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

Lalu, bila stack ini juga harus dapat diakses dari internet:

```bash
# isi CLOUDFLARE_TUNNEL_TOKEN di .env terlebih dahulu (lihat bagian 3)
./scripts/redline.sh tunnel
./scripts/redline.sh status
```

`status` memeriksa tiga lapis sekaligus — localhost, IP LAN, dan URL publik —
jadi bila suatu saat backend hanya hidup di komputer ini, baris mana yang gagal
langsung menunjukkan penyebabnya:

| Baris gagal | Artinya |
|---|---|
| `API` | Container/aplikasi bermasalah — cek `./scripts/redline.sh logs app` |
| `LAN` | Firewall host memblokir port (cek `sudo ufw status`) |
| `Publik` | Tunnel/ingress bermasalah — hampir selalu Service URL masih menunjuk IP LAN lama |

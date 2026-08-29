# Redline Komputer — Backend & Sistem Informasi (SIRC)

Platform Backend REST API dan Sistem Informasi Manajemen Penjualan (POS), Servis/Reparasi Komputer, dan Katalog Produk untuk Toko Redline Komputer Salatiga.

Dapat digunakan secara mandiri (Full-Stack MVC Laravel) ataupun sebagai **Backend REST API (Headless API)** untuk frontend modern seperti **Next.js**, React, Vue, atau Mobile App.

---

## 🚀 Menjalankan Server di Laptop / Komputer Baru

Jika Anda meng-clone project ini di laptop lain, cukup ikuti 3 langkah berikut:

### 1. Prasyarat
- **Docker** & **Docker Compose** sudah terpasang di laptop.

### 2. Setup Environment
```bash
git clone https://github.com/yhnzzzwp/redlinecomp.git
cd redlinecomp

# Salin template env
cp .env.example .env
```

### 3. Jalankan Docker Container
```bash
# Jalankan container (DB MariaDB, App PHP-FPM, Web Nginx, Adminer)
docker compose up -d --build

# Generate App Key & Jalankan Migrasi + Seeder
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Server backend sudah aktif dan siap melayani request! 🎉

---

## 🌐 Daftar Endpoint & Port Lokal

| Service | Port / URL | Keterangan |
|---|---|---|
| **REST API Base** | `http://localhost:8000/api/v1` | Base URL seluruh REST API |
| **API Health Check** | `http://localhost:8000/api/v1/health` | Status kesehatan backend |
| **API Katalog** | `http://localhost:8000/api/v1/katalog` | Katalog produk publik |
| **API Login** | `http://localhost:8000/api/v1/auth/login` | Login pegawai & terbitkan token |
| **Adminer (Database GUI)** | `http://localhost:8081` | Server: `db`, User: `redline`, Pass: `redline_secret`, DB: `redline` |

---

## 🔗 Menghubungkan Backend ke Frontend (Next.js / Vercel)

1. **Jalankan Tunnel Publik (Cloudflare / Ngrok / Localtunnel):**
   ```bash
   # Contoh quick tunnel cloudflared
   docker run --rm --net=host cloudflare/cloudflared:latest tunnel --url http://localhost:8000
   ```
2. **Set Environment Variable di Frontend (Vercel):**
   - **Key**: `API_BASE_URL` (tanpa awalan `NEXT_PUBLIC_` — dibaca hanya di
     sisi server Next.js, lihat `src/lib/server/backend.ts`)
   - **Value**: `https://<subdomain-tunnel-anda>/api/v1`
   - Lakukan **Redeploy** di Vercel.

---

## 📚 Dokumentasi Lengkap
- [Panduan Lengkap REST API v1](docs/10-api-backend.md)
- [Panduan Deploy, Cloudflare Tunnel, dan Integrasi Vercel](docs/11-panduan-deploy-dan-koneksi.md)

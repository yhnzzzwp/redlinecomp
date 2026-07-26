# 08 — Deploy Produksi (VPS + Docker + Subdomain)

Prosedur lengkap menerbitkan SIRC ke produksi dengan tiga portal:
publik (`<domain>`), karyawan (`karyawan.<domain>`), admin (`admin.<domain>`).
Contoh di bawah memakai `redlinekomputer.id` — ganti sesuai domain asli.

## A. Prasyarat
- VPS Linux (Ubuntu 22.04+/Debian 12+), 1 vCPU / 1 GB RAM cukup untuk memulai.
- Docker + Docker Compose plugin terpasang.
- Domain dengan akses pengaturan DNS.

## B. DNS — tiga record ke server yang sama
| Tipe | Nama | Nilai |
|------|------|-------|
| A | `redlinekomputer.id` | IP VPS |
| A | `admin.redlinekomputer.id` | IP VPS |
| A | `karyawan.redlinekomputer.id` | IP VPS |

> Jangan gunakan wildcard `*.domain` di DNS — cukup dua subdomain ini,
> supaya subdomain asing tidak ikut mengarah ke aplikasi.

## C. Kode & konfigurasi
```bash
git clone https://github.com/yhnzzzwp/redlinecomp.git redline && cd redline
cp .env.example .env
```

Isi `.env` produksi (nilai WAJIB diubah):
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://redlinekomputer.id

REDLINE_PUBLIC_HOST=redlinekomputer.id
REDLINE_STAFF_HOST=karyawan.redlinekomputer.id
REDLINE_ADMIN_HOST=admin.redlinekomputer.id

SESSION_SECURE_COOKIE=true

DB_PASSWORD=<password kuat baru>
DB_ROOT_PASSWORD=<password kuat baru — beda>
```

Lalu:
```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force --seed   # --seed hanya deploy perdana
docker compose exec app php artisan config:cache && \
docker compose exec app php artisan route:cache && \
docker compose exec app php artisan view:cache
```

> Ganti password akun demo `owner`/`rijal` segera lewat menu Akun Pegawai.
> `TrustHosts` aktif otomatis saat `APP_ENV=production` — host di luar tiga
> host portal ditolak.

## D. TLS + reverse proxy host
Nginx bawaan compose melayani HTTP di port 8080. Di depan, pasang reverse
proxy host + Let's Encrypt. Cara termudah — **Caddy** (TLS otomatis):

```bash
sudo apt install caddy
```

`/etc/caddy/Caddyfile`:
```
redlinekomputer.id, admin.redlinekomputer.id, karyawan.redlinekomputer.id {
    reverse_proxy 127.0.0.1:8080
}
```
`sudo systemctl reload caddy` — sertifikat ketiga host diurus otomatis.

Alternatif nginx host + certbot: terbitkan tiga sertifikat
(`certbot --nginx -d redlinekomputer.id -d admin... -d karyawan...`) dan isi
`server_name` eksplisit tiga host (lihat komentar `docker/nginx/default.conf`).

**Jangan buka port ke publik selain 80/443** — 8080 (nginx container),
3307 (MySQL), 8081/8082 (GUI DB) hanya untuk localhost/firewall internal.
```bash
sudo ufw allow 80,443/tcp && sudo ufw enable
```

## E. Backup harian (kewajiban SRS — retensi ≥30 hari)
```bash
crontab -e
# tambahkan:
0 2 * * * /path/ke/redline/scripts/backup-db.sh >> /path/ke/redline/storage/logs/backup.log 2>&1
```
- Hasil di `backups/redline-YYYYmmdd-HHMMSS.sql.gz`; yang lebih tua dari 30 hari terhapus otomatis.
- **Uji restore berkala** (minimal tiap bulan):
  ```bash
  scripts/restore-db.sh backups/redline-<terbaru>.sql.gz redline_uji_restore
  # bandingkan jumlah baris, lalu DROP DATABASE redline_uji_restore
  ```
- Salin folder `backups/` ke lokasi kedua (rclone/S3/disk lain) bila memungkinkan.

## F. Smoke test pasca-deploy
```bash
scripts/smoke-produksi.sh redlinekomputer.id admin.redlinekomputer.id karyawan.redlinekomputer.id
```
Memverifikasi otomatis: beranda 200; `/login` & `/dashboard` 404 di publik;
login kedua portal 200 + `X-Robots-Tag: noindex` + robots `Disallow: /`;
CSP publik tanpa `unsafe-eval`; HSTS; cookie `Secure`+`HttpOnly`; Host asing
ditolak (TrustHosts). **Semua harus lulus sebelum diumumkan.**

## G. Update rutin
```bash
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache && \
docker compose exec app php artisan route:cache && \
docker compose exec app php artisan view:cache
scripts/smoke-produksi.sh redlinekomputer.id admin.redlinekomputer.id karyawan.redlinekomputer.id
```

## H. Pemulihan bencana (ringkas)
1. VPS baru → langkah C–D.
2. Salin file backup terakhir ke `backups/`.
3. `scripts/restore-db.sh -y backups/<file>.sql.gz`
4. Smoke test (F).

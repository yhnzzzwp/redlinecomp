# Dokumentasi Backend REST API — Redline Komputer (v1)

API RESTful lengkap untuk integrasi website publik, POS kasir, dashboard staf, dan admin console (siap untuk migrasi/integrasi Next.js, Mobile App, atau Client Frontend modern).

---

## 1. Konsep Dasar & Autentikasi

- **Base URL**: `http://localhost:8080/api/v1` (atau domain produksi sesuai konfigurasi)
- **Format Data**: JSON (`Content-Type: application/json` & `Accept: application/json`)
- **Autentikasi**: Bearer Token
  ```http
  Authorization: Bearer rl_tok_xxx...
  ```
- **CORS**: Sudah diaktifkan untuk frontend origins (`allowed_origins: *`, `allowed_methods: *`).

### Standar Format Respon

**Respon Sukses:**
```json
{
  "status": "success",
  "message": "Pesan opsional",
  "data": { ... },
  "pagination": { ... } // opsional untuk daftar berhalaman
}
```

**Respon Galat:**
```json
{
  "status": "error",
  "message": "Keterangan kesalahan",
  "errors": { ... } // opsional untuk validasi input
}
```

---

## 2. Ringkasan Endpoint

| Method | Endpoint | Hak Akses | Deskripsi |
|--------|----------|-----------|-----------|
| **GET** | `/health` | Publik | Status kesehatan backend API |
| **POST** | `/auth/login` | Publik | Login pegawai & terbitkan Bearer token |
| **GET** | `/auth/me` | Autentikasi | Dapatkan profil user saat ini |
| **POST** | `/auth/logout` | Autentikasi | Cabut token autentikasi |
| **PUT** | `/auth/profile` | Autentikasi | Perbarui data profil sendiri |
| **PUT** | `/auth/password` | Autentikasi | Ganti password akun sendiri |
| **GET** | `/katalog` | Publik | Daftar katalog produk publik (filter & pagination) |
| **GET** | `/katalog/{id}` | Publik | Detail produk + produk terkait |
| **GET** | `/kategori` | Publik | Daftar kategori & jumlah produk aktif |
| **GET** | `/promo` | Publik | Daftar promo yang sedang aktif |
| **POST** | `/promo/cek` | Publik | Cek & validasi kode promo terhadap subtotal |
| **GET** | `/service/cek?resi=...` | Publik | Lacak status perbaikan servis via nomor resi |
| **GET** | `/perangkat/{kode}` | Publik | Cek data & riwayat perangkat via kode QR |
| **POST** | `/pos/sync` | Publik/POS | Sinkronisasi transaksi offline (batch/idempotent) |
| **GET** | `/pos/items` | Staff/Owner | Data master produk & servis siap diambil untuk POS |
| **POST** | `/pos/checkout` | Staff/Owner | Transaksi POS kasir (produk & servis + promo) |
| **GET** | `/pos/nota/{transaksi}` | Staff/Owner | Data invoice / nota digital |
| **GET** | `/pos/struk/{transaksi}` | Staff/Owner | Data struk thermal printer (58mm/80mm) |
| **GET** | `/admin/dashboard` | Staff/Owner | Ringkasan KPI penjualan & status servis hari ini |
| **GET** | `/admin/produk` | Staff/Owner | Daftar produk internal (filter & cari) |
| **POST** | `/admin/produk` | Staff/Owner | Tambah produk baru |
| **GET** | `/admin/produk/{id}` | Staff/Owner | Detail produk |
| **PUT** | `/admin/produk/{id}` | Staff/Owner | Perbarui produk |
| **DELETE** | `/admin/produk/{id}` | Staff/Owner | Hapus produk |
| **GET** | `/admin/kategori` | Staff/Owner | Daftar kategori produk |
| **POST** | `/admin/kategori` | Staff/Owner | Tambah kategori baru |
| **PUT** | `/admin/kategori/{id}` | Staff/Owner | Perbarui kategori |
| **DELETE** | `/admin/kategori/{id}` | Staff/Owner | Hapus kategori (hanya bila kosong) |
| **GET** | `/admin/services` | Staff/Owner | Daftar tiket servis |
| **POST** | `/admin/services` | Staff/Owner | Buat tiket servis baru & link perangkat |
| **GET** | `/admin/services/{id}` | Staff/Owner | Detail tiket servis + sparepart + timeline |
| **PUT** | `/admin/services/{id}` | Staff/Owner | Perbarui data servis |
| **POST** | `/admin/services/{id}/status` | Staff/Owner | Ganti status servis (hasilkan link WhatsApp) |
| **POST** | `/admin/services/{id}/parts` | Staff/Owner | Tambah sparepart ke tiket servis |
| **DELETE** | `/admin/services/{id}/parts/{part_id}` | Staff/Owner | Hapus sparepart dari tiket servis |
| **GET** | `/admin/perangkat` | Staff/Owner | Daftar perangkat customer terdaftar |
| **POST** | `/admin/perangkat` | Staff/Owner | Daftarkan perangkat baru |
| **GET** | `/admin/perangkat/{id}` | Staff/Owner | Detail perangkat & riwayat servis |
| **PUT** | `/admin/perangkat/{id}` | Staff/Owner | Perbarui data perangkat |
| **GET** | `/admin/transaksi` | Staff/Owner | Riwayat transaksi penjualan |
| **GET** | `/admin/transaksi/{id}` | Staff/Owner | Detail transaksi |
| **POST** | `/admin/transaksi/{id}/void` | **Owner Only** | Batalkan / void transaksi |
| **GET** | `/admin/analytics` | **Owner Only** | Analitik penjualan, metode bayar & tren |
| **GET** | `/admin/promos` | **Owner Only** | Kelola semua promo |
| **POST** | `/admin/promos` | **Owner Only** | Buat promo baru |
| **GET** | `/admin/promos/{id}` | **Owner Only** | Detail promo |
| **PUT** | `/admin/promos/{id}` | **Owner Only** | Perbarui promo |
| **DELETE** | `/admin/promos/{id}` | **Owner Only** | Hapus promo |
| **POST** | `/admin/promos/{id}/toggle` | **Owner Only** | Aktifkan/nonaktifkan promo |
| **GET** | `/admin/pegawai` | **Owner Only** | Daftar semua akun staf/pegawai |
| **POST** | `/admin/pegawai` | **Owner Only** | Tambah akun pegawai baru |
| **GET** | `/admin/pegawai/{id}` | **Owner Only** | Detail akun pegawai |
| **PUT** | `/admin/pegawai/{id}` | **Owner Only** | Perbarui akun & hak akses pegawai |
| **DELETE** | `/admin/pegawai/{id}` | **Owner Only** | Hapus / nonaktifkan pegawai |
| **POST** | `/admin/pegawai/{id}/revoke-sessions` | **Owner Only** | Cabut semua sesi/token aktif pegawai |

---

## 3. Contoh Penggunaan API

### A. Login Pegawai (`POST /api/v1/auth/login`)
**Request:**
```json
{
  "username": "owner",
  "password": "password",
  "portal": "admin"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "token": "rl_tok_9a8b7c6d5e4f3a2b1c...",
    "user": {
      "id": 1,
      "nama_pegawai": "Owner Redline",
      "username": "owner",
      "email": "owner@redline.test",
      "role": "Owner",
      "is_owner": true
    }
  }
}
```

### B. Checkout POS Kasir (`POST /api/v1/pos/checkout`)
Header: `Authorization: Bearer <token>`
**Request:**
```json
{
  "items": [
    { "tipe": "produk", "produk_id": 1, "jumlah": 2, "harga": 150000 },
    { "tipe": "service", "service_id": 4, "jumlah": 1, "harga": 250000 }
  ],
  "metode_bayar": "Tunai",
  "bayar": 600000,
  "kode_promo": "DISKON10",
  "nama_pembeli": "Budi",
  "nomor_hp_pembeli": "081234567890"
}
```

### C. Update Status Servis & Kirim Notifikasi WA (`POST /api/v1/admin/services/{id}/status`)
Header: `Authorization: Bearer <token>`
**Request:**
```json
{
  "status": "Selesai",
  "catatan": "Unit telah selesai dibersihkan dan diganti thermal paste."
}
```
**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Status servis diubah menjadi Selesai.",
  "data": {
    "service": { ... },
    "wa_link": "https://wa.me/6281234567890?text=Halo%20Budi...%20servis%20Anda%20telah%20SELESAI"
  }
}
```

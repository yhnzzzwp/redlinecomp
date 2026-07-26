# 01 — Arsitektur

## Pola: MVC + Service Layer
SRS mewajibkan **MVC**. Agar mudah dirawat, logika bisnis tidak ditaruh di controller
melainkan di **Service classes** (`app/Services`). Controller hanya orkestrasi tipis:
terima request → panggil service → kembalikan view/redirect.

Service digunakan untuk logika transaksional/multi-langkah (POS checkout, service ticket). Untuk CRUD sederhana (Promo, Pegawai), Eloquent langsung di controller.

```
Request → Route → (Middleware auth/role) → Controller → Service → Model → DB
                                                     ↘ View (Blade)
```

## Tiga Portal, Tiga Host (pemisahan subdomain)
Zona SRS §2.1 dipetakan ke **host terpisah** (config `redline.hosts`, env `REDLINE_*_HOST`):

| Portal | Host lokal | Isi | Role login |
|--------|-----------|-----|------------|
| Publik | `localhost:8080` | landing, katalog, detail produk, cek servis, cek nota | — (tanpa login) |
| Karyawan | `karyawan.localhost:8080` | dashboard, POS, produk, servis, transaksi | `Karyawan` |
| Admin | `admin.localhost:8080` | semua fitur karyawan **+** analytics, promo, akun pegawai, void/export | `Owner` |

Mekanisme (lihat `app/Support/Portal.php` + middleware `EnsurePortal`):
- Route internal dibungkus `portal:internal` → dari host publik **404** (keberadaan portal disembunyikan), dan role user wajib cocok dengan portalnya (Owner ↔ admin, Karyawan ↔ karyawan).
- Route publik dibungkus `portal:public` → diakses dari host portal dialihkan ke login portal tsb.
- Login per portal menambahkan syarat `role` pada `Auth::attempt` — akun role lain gagal dengan pesan generik (tidak membocorkan keberadaan akun).
- Cookie sesi **host-only** (`SESSION_DOMAIN=null`) → sesi tiga portal saling terisolasi.
- `EnsurePortal` didaftarkan ke *middleware priority list* sebelum `Authenticate` supaya cek host berjalan lebih dulu (404, bukan redirect login).

Browser modern me-resolve `*.localhost` ke `127.0.0.1` tanpa konfigurasi. Produksi: arahkan `admin.<domain>` dan `karyawan.<domain>` ke server yang sama, isi env `REDLINE_PUBLIC_HOST`, `REDLINE_STAFF_HOST`, `REDLINE_ADMIN_HOST`.

## Struktur folder penting
```
app/
├── Enums/            RolePegawai, TipeItem, TipePromo, StatusService, MetodeBayar
├── Models/           9 entitas Eloquent (relasi + cast enum + $fillable)
├── Services/         Logika bisnis: PosService, ServiceTicketService, PromoService, dst.
├── Http/
│   ├── Controllers/  Public/  &  Internal/  (dipisah namespace)
│   ├── Requests/     Form Request (validasi terpusat)
│   └── Middleware/   EnsureOwner, EnsurePortal, SecurityHeaders
├── Support/          Portal (peta host → portal, role, label)
resources/views/
├── layouts/          app (internal), public
├── components/       ui/* (card, badge, sidebar) — anti-duplikasi
├── internal/         halaman internal
└── public/           halaman publik
config/redline.php    Konfigurasi domain (metode bayar, nomor WA, ambang stok)
docker/               Dockerfile, nginx, php.ini
```

## Prinsip modularitas
1. **Satu fitur = satu Service** → mengubah promo tak menyentuh POS.
2. **Blade components** untuk UI berulang (memperbaiki "sidebar digambar 8×" di desain Figma).
3. **Config-driven** — status, metode bayar, dsb. tidak hardcode.
4. **Enum** untuk semua nilai berhingga → tidak ada "magic string".

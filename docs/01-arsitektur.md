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

## Dua Zona (sesuai SRS §2.1)
- **Publik** (tanpa login): landing, katalog, detail produk, cek servis, cek nota.
- **Internal** (setelah login): POS, produk, servis, promo*, laporan*, akun pegawai* (`*` = khusus Owner).

## Struktur folder penting
```
app/
├── Enums/            RolePegawai, TipeItem, TipePromo, StatusService, MetodeBayar
├── Models/           9 entitas Eloquent (relasi + cast enum + $fillable)
├── Services/         Logika bisnis: PosService, ServiceTicketService, PromoService, dst.
├── Http/
│   ├── Controllers/  Public/  &  Internal/  (dipisah namespace)
│   ├── Requests/     Form Request (validasi terpusat)
│   └── Middleware/   EnsureOwner, SecurityHeaders
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

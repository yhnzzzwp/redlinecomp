# 04 — Keamanan

Pertahanan berlapis. Target: tidak bisa ditembus dengan tools umum (SQLi, XSS, CSRF, brute-force, IDOR, mass-assignment).

## Lapisan & status
| Ancaman | Mitigasi | Status |
|---------|----------|--------|
| SQL Injection | Eloquent/Query Builder → **prepared statements** selalu; tanpa raw SQL dari input | ✅ bawaan |
| XSS | Blade `{{ }}` **auto-escape**; `{!! !!}` hanya untuk konten tepercaya | ✅ bawaan |
| CSRF | `@csrf` di semua form POST/PUT/DELETE; token diverifikasi middleware | ✅ bawaan |
| Password | disimpan **bcrypt** (cast `hashed`, `BCRYPT_ROUNDS=12`) | ✅ |
| Brute-force login | **rate limit** 5 percobaan/menit per IP+username (throttle) | ⏳ task auth |
| Mass assignment | `$fillable` eksplisit di semua model (tidak ada `$guarded=[]`) | ✅ |
| Otorisasi (role) | **enforcement di server** via middleware `EnsureOwner` + Policy, bukan sekadar sembunyikan menu (§3.5) | ⏳ task auth |
| IDOR | route-model binding + Policy cek kepemilikan/role | ⏳ |
| Session hijack | cookie `HttpOnly` + `SameSite=Lax` + `Secure`(prod); timeout **30 menit** (§3.5); regenerate id saat login | ✅/⏳ |
| Clickjacking | header `X-Frame-Options: SAMEORIGIN` | ✅ nginx + middleware |
| MIME sniffing | `X-Content-Type-Options: nosniff` | ✅ |
| Info leak | `APP_DEBUG=false` + `expose_php=Off` di produksi; file `.env/.sql/.log` diblok nginx | ✅ |
| Upload berbahaya | validasi mime & ukuran di Form Request; simpan di luar webroot | ⏳ saat fitur upload |

## Tools audit yang dijalankan
```bash
composer audit           # CVE pada dependensi PHP
npm audit                # CVE pada dependensi JS
./vendor/bin/phpstan analyse   # Larastan: analisis statis (bug sebelum runtime)
./vendor/bin/pint              # konsistensi gaya kode
```

## Checklist sebelum deploy produksi
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` di-generate & rahasia
- [ ] HTTPS aktif (`SESSION_SECURE_COOKIE=true`), redirect http→https
- [ ] Password DB kuat (bukan default `redline_secret`)
- [ ] `php artisan config:cache route:cache view:cache`
- [ ] Backup DB harian, retensi ≥30 hari (§3.5)
- [ ] `composer audit` & `npm audit` bersih

## Catatan deviasi versi
SRS menyebut "Laravel 11.x"; implementasi memakai **Laravel 13** (rilis terbaru dengan
dukungan keamanan aktif). API kompatibel; keputusan diambil demi patch keamanan terkini.

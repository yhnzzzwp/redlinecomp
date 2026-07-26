# 04 — Keamanan

Pertahanan berlapis. Target: tidak bisa ditembus dengan tools umum (SQLi, XSS, CSRF, brute-force, IDOR, mass-assignment).

## Lapisan & status
| Ancaman | Mitigasi | Status |
|---------|----------|--------|
| SQL Injection | Eloquent/Query Builder → **prepared statements** selalu; tanpa raw SQL dari input | ✅ bawaan |
| XSS | Blade `{{ }}` **auto-escape**; `{!! !!}` hanya untuk konten tepercaya | ✅ bawaan |
| CSRF | `@csrf` di semua form POST/PUT/DELETE; token diverifikasi middleware | ✅ bawaan |
| Password | disimpan **bcrypt** (cast `hashed`, `BCRYPT_ROUNDS=12`) | ✅ |
| Brute-force login | **rate limit** 5 percobaan/menit per **portal+username+IP** + throttle route 10/menit; login gagal dicatat ke log | ✅ |
| Pemisahan permukaan serangan | **login admin & karyawan di subdomain terpisah** (`admin.*` / `karyawan.*`); zona internal 404 dari host publik; role wajib cocok dengan portal saat login **dan** di tiap request (`EnsurePortal`) | ✅ |
| Enumerasi akun lintas portal | login role lain di portal yang salah → pesan galat **generik** yang sama | ✅ |
| Mass assignment | `$fillable` eksplisit di semua model (tidak ada `$guarded=[]`) | ✅ |
| Otorisasi (role) | **enforcement di server**: `EnsurePortal` (portal ↔ role) + `EnsureOwner` (fitur owner), bukan sekadar sembunyikan menu (§3.5) | ✅ |
| IDOR | route-model binding + pemisahan portal per role | ✅ |
| Session hijack | cookie `HttpOnly` + `SameSite=Lax` + `Secure`(prod) + **host-only** (`SESSION_DOMAIN=null`) → sesi antar subdomain terisolasi; timeout **30 menit** (§3.5); regenerate id saat login | ✅ |
| Host-header injection | `TrustHosts` — hanya tiga host portal yang diterima (aktif otomatis di non-local) | ✅ |
| Clickjacking | header `X-Frame-Options: SAMEORIGIN` + CSP `frame-ancestors` | ✅ |
| MIME sniffing | `X-Content-Type-Options: nosniff` | ✅ |
| Pengindeksan portal | `X-Robots-Tag: noindex` + `robots.txt` dinamis (`Disallow: /` di host portal) + `<meta name="robots">` di login | ✅ |
| Supply-chain font/CDN | font **self-host** di `public/fonts` — tanpa CDN pihak ketiga, CSP `font-src 'self'` | ✅ |
| Info leak | `APP_DEBUG=false` + `expose_php=Off` di produksi; file `.env/.sql/.log` diblok nginx | ✅ |
| Upload berbahaya | validasi mime & ukuran di Form Request; simpan di luar webroot | ⏳ saat fitur upload |

Header tambahan: `Content-Security-Policy` (nonce Vite), `Referrer-Policy`,
`Permissions-Policy`, `Cross-Origin-Opener-Policy: same-origin`, HSTS (saat HTTPS).

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

#!/usr/bin/env bash
# ============================================================
# Smoke test pasca-deploy SIRC — verifikasi perilaku tiga portal.
#
# Pemakaian (produksi):
#   scripts/smoke-produksi.sh redlinekomputer.id admin.redlinekomputer.id karyawan.redlinekomputer.id
#
# Uji lokal (Docker):
#   SCHEME=http PORT=:8080 scripts/smoke-produksi.sh localhost admin.localhost karyawan.localhost
# ============================================================
set -u

PUBLIK="${1:?host publik, mis. redlinekomputer.id}"
ADMIN="${2:?host admin, mis. admin.redlinekomputer.id}"
STAFF="${3:?host karyawan, mis. karyawan.redlinekomputer.id}"
SCHEME="${SCHEME:-https}"
PORT="${PORT:-}"

LULUS=0; GAGAL=0

cek() { # cek <deskripsi> <ekspektasi-regex> <aktual>
    if printf '%s' "$3" | grep -qE "$2"; then
        printf '  ✓ %s\n' "$1"; LULUS=$((LULUS + 1))
    else
        printf '  ✗ %s\n      ekspektasi /%s/, aktual: %s\n' "$1" "$2" "$3"; GAGAL=$((GAGAL + 1))
    fi
}

ambil_status()  { curl -sk -o /dev/null -w '%{http_code}' "$1"; }
ambil_header()  { curl -skI "$1" | tr -d '\r'; }

echo "== PORTAL PUBLIK ($SCHEME://$PUBLIK$PORT) =="
cek "beranda 200"                 '^200$' "$(ambil_status "$SCHEME://$PUBLIK$PORT/")"
cek "/login tersembunyi (404)"    '^404$' "$(ambil_status "$SCHEME://$PUBLIK$PORT/login")"
cek "/dashboard tersembunyi (404)" '^404$' "$(ambil_status "$SCHEME://$PUBLIK$PORT/dashboard")"
H="$(ambil_header "$SCHEME://$PUBLIK$PORT/")"
cek "tanpa X-Robots-Tag"          '^TIDAK-ADA$' "$(printf '%s' "$H" | grep -i 'X-Robots-Tag' || echo 'TIDAK-ADA')"
cek "CSP tanpa unsafe-eval"       '^TIDAK-ADA$' "$(printf '%s' "$H" | grep -i 'Content-Security-Policy' | grep -o 'unsafe-eval' || echo 'TIDAK-ADA')"
cek "robots.txt mengizinkan"      'Disallow:\s*$' "$(curl -sk "$SCHEME://$PUBLIK$PORT/robots.txt")"

echo "== PORTAL ADMIN ($SCHEME://$ADMIN$PORT) =="
cek "/login admin 200"            '^200$' "$(ambil_status "$SCHEME://$ADMIN$PORT/login")"
HA="$(ambil_header "$SCHEME://$ADMIN$PORT/login")"
cek "noindex (X-Robots-Tag)"      'noindex' "$(printf '%s' "$HA" | grep -i 'X-Robots-Tag' || echo 'TIDAK-ADA')"
cek "robots.txt melarang"         'Disallow: /' "$(curl -sk "$SCHEME://$ADMIN$PORT/robots.txt")"
cek "cookie sesi HttpOnly"        '[Hh]ttp[Oo]nly' "$(printf '%s' "$HA" | grep -i 'set-cookie' || echo 'TIDAK-ADA')"

echo "== PORTAL KARYAWAN ($SCHEME://$STAFF$PORT) =="
cek "/login karyawan 200"         '^200$' "$(ambil_status "$SCHEME://$STAFF$PORT/login")"

if [ "$SCHEME" = "https" ]; then
    echo "== KHUSUS PRODUKSI (HTTPS) =="
    cek "HSTS aktif"              'strict-transport-security' "$(printf '%s' "$H" | tr 'A-Z' 'a-z' | grep 'strict-transport-security' || echo 'TIDAK-ADA')"
    cek "cookie Secure"           '[Ss]ecure' "$(printf '%s' "$HA" | grep -i 'set-cookie' || echo 'TIDAK-ADA')"
    cek "Host asing ditolak (TrustHosts)" '^(400|403|421|301|302)$' \
        "$(curl -sk -o /dev/null -w '%{http_code}' -H 'Host: evil.example.com' "$SCHEME://$PUBLIK$PORT/")"
fi

echo
echo "Hasil: $LULUS lulus, $GAGAL gagal."
[ "$GAGAL" -eq 0 ]

#!/usr/bin/env bash
#
# Kendali cepat stack Redline.
#
# Konfigurasi tersimpan di .env dan data database di volume docker bernama
# "dbdata" — keduanya bertahan saat container dimatikan. Jadi menyalakan ulang
# TIDAK perlu setting atau migrasi apa pun lagi.
#
# Pemakaian:  ./scripts/redline.sh {start|stop|restart|status|logs|tunnel|tools|down}

set -euo pipefail

cd "$(dirname "$0")/.."
APP_URL="http://localhost:${APP_PORT:-8000}"

warna() { printf '\033[%sm%s\033[0m\n' "$1" "$2"; }
ok()    { warna '0;32' "  ✔ $1"; }
gagal() { warna '0;31' "  ✘ $1"; }
info()  { warna '0;36' "$1"; }

tunggu_sehat() {
  info "Menunggu backend siap…"
  for i in $(seq 1 40); do
    kode=$(curl -s -o /dev/null -w '%{http_code}' --max-time 3 "$APP_URL/api/v1/health" || true)
    if [ "$kode" = "200" ]; then
      ok "API sehat ($APP_URL/api/v1/health)"
      return 0
    fi
    sleep 2
  done
  gagal "Backend belum merespons setelah 80 detik."
  echo "     Cek log: ./scripts/redline.sh logs"
  return 1
}

status() {
  info "Container:"
  docker compose ps --format '  {{.Name}}  {{.Status}}  {{.Ports}}' 2>/dev/null || docker compose ps

  echo
  info "Kesehatan:"
  kode=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "$APP_URL/api/v1/health" || echo 000)
  [ "$kode" = "200" ] && ok "API  $APP_URL/api/v1/health" || gagal "API tidak merespons (kode $kode)"

  kode=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 http://localhost:3000/ || echo 000)
  [ "$kode" = "200" ] && ok "Frontend localhost:3000" \
    || echo "  · Frontend tidak jalan — jalankan: cd ../FE-redline && npm run dev"

  if docker ps --format '{{.Names}}' | grep -qi cloudflared; then
    ok "Tunnel Cloudflare aktif (dikelola compose project terpisah)"
  else
    echo "  · Tunnel tidak jalan — situs tidak dapat diakses dari internet"
  fi
}

case "${1:-}" in
  start)
    info "Menyalakan Redline…"
    docker compose up -d
    tunggu_sehat
    echo
    echo "  Portal publik : $APP_URL"
    echo "  Portal staf   : http://karyawan.localhost:${APP_PORT:-8000}"
    echo "  Portal admin  : http://admin.localhost:${APP_PORT:-8000}"
    echo "  API           : $APP_URL/api/v1"
    ;;

  stop)
    info "Mematikan Redline (container disimpan, data aman)…"
    docker compose stop
    ok "Berhenti. Nyalakan lagi dengan: ./scripts/redline.sh start"
    ;;

  restart)
    docker compose restart
    tunggu_sehat
    ;;

  status)  status ;;

  logs)
    docker compose logs -f --tail=100 "${2:-}"
    ;;

  tunnel)
    if ! grep -q '^CLOUDFLARE_TUNNEL_TOKEN=.\+' .env 2>/dev/null; then
      gagal "CLOUDFLARE_TUNNEL_TOKEN belum diisi di .env."
      echo "     Tanpa token, cloudflared membuka QUICK TUNNEL: URL trycloudflare.com"
      echo "     acak yang mengekspos backend ini ke internet TANPA autentikasi."
      exit 1
    fi
    docker compose --profile tunnel up -d tunnel
    ok "Tunnel dijalankan. Log: ./scripts/redline.sh logs tunnel"
    ;;

  tools)
    info "Menyalakan Adminer & phpMyAdmin (hanya localhost)…"
    docker compose --profile tools up -d
    ok "Adminer     http://localhost:8081"
    ok "phpMyAdmin  http://localhost:8082"
    echo "  Matikan lagi: docker compose --profile tools stop adminer phpmyadmin"
    ;;

  down)
    info "Menghapus container (volume dbdata TIDAK dihapus, data aman)…"
    docker compose down
    ok "Container dihapus. 'start' akan membuatnya ulang."
    warna '0;33' "  Catatan: JANGAN pakai 'docker compose down -v' — itu MENGHAPUS database."
    ;;

  *)
    echo "Pemakaian: $0 {start|stop|restart|status|logs|tunnel|tools|down}"
    echo
    echo "  start    nyalakan seluruh stack lalu tunggu API sehat"
    echo "  stop     matikan (paling sering dipakai; data & container disimpan)"
    echo "  restart  mulai ulang container"
    echo "  status   lihat kondisi container, API, frontend, dan tunnel"
    echo "  logs     ikuti log  (mis. logs app)"
    echo "  tunnel   nyalakan tunnel Cloudflare (wajib ada token di .env)"
    echo "  tools    nyalakan Adminer/phpMyAdmin sementara"
    echo "  down     hapus container — data database tetap aman"
    exit 1
    ;;
esac

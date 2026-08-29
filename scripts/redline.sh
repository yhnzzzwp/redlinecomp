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

# Nilai .env dibaca satu per satu; skrip ini sengaja tidak meng-export seluruh
# isi .env (ada kredensial di dalamnya).
env_get() { sed -n "s/^$1=//p" .env 2>/dev/null | tail -1 | tr -d '"'"'"'"'; }

APP_PORT="${APP_PORT:-$(env_get APP_PORT)}"
APP_PORT="${APP_PORT:-8000}"
APP_URL="http://localhost:${APP_PORT}"

# Alamat publik backend (lewat Cloudflare Tunnel). Kosongkan bila belum ada.
PUBLIC_API_URL="$(env_get REDLINE_PUBLIC_API_URL)"

warna() { printf '\033[%sm%s\033[0m\n' "$1" "$2"; }

# Kode HTTP sebuah URL, atau 000 bila tidak dapat dihubungi. curl sudah
# mencetak "000" saat gagal, jadi jangan tambahkan '|| echo 000' — hasilnya
# jadi "000000" dan tidak pernah cocok dengan perbandingan mana pun.
kode_http() {
  local kode
  kode=$(curl -s -o /dev/null -w '%{http_code}' --max-time "${2:-5}" "$1" || true)
  printf '%s' "${kode:-000}"
}
ok()    { warna '0;32' "  ✔ $1"; }
gagal() { warna '0;31' "  ✘ $1"; }
info()  { warna '0;36' "$1"; }

tunggu_sehat() {
  info "Menunggu backend siap…"
  for i in $(seq 1 40); do
    kode=$(kode_http "$APP_URL/api/v1/health" 3)
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
  kode=$(kode_http "$APP_URL/api/v1/health")
  [ "$kode" = "200" ] && ok "API  $APP_URL/api/v1/health" || gagal "API tidak merespons (kode $kode)"

  # Akses dari mesin LAIN di jaringan yang sama. Container mengikat 0.0.0.0,
  # jadi kegagalan di sini biasanya firewall host (ufw), bukan aplikasi.
  lan=$(hostname -I 2>/dev/null | awk '{print $1}')
  if [ -n "${lan:-}" ]; then
    kode=$(kode_http "http://$lan:${APP_PORT}/api/v1/health")
    [ "$kode" = "200" ] && ok "LAN  http://$lan:${APP_PORT}/api/v1" \
      || gagal "LAN http://$lan:${APP_PORT} tidak merespons (kode $kode) — cek 'sudo ufw status'"
  fi

  # Akses dari INTERNET. Paling sering rusak setelah server dipindah ke
  # jaringan lain: ingress Cloudflare Tunnel masih menunjuk IP LAN yang lama.
  # Service URL di dashboard harus http://host.docker.internal:8000, bukan IP.
  if [ -n "${PUBLIC_API_URL:-}" ]; then
    kode=$(kode_http "${PUBLIC_API_URL%/}/health" 10)
    if [ "$kode" = "200" ]; then
      ok "Publik $PUBLIC_API_URL"
    else
      gagal "Publik $PUBLIC_API_URL tidak merespons (kode $kode)"
      echo "     IP LAN host sekarang: ${lan:-tidak diketahui}"
      echo "     Cek ingress tunnel: docker logs --tail=20 \$(docker ps -qf name=cloudflared)"
      echo "     Bila lognya 'dial tcp <ip-lama>:8000: i/o timeout', ubah Service URL"
      echo "     public hostname di dashboard Cloudflare menjadi http://host.docker.internal:8000"
      echo "     (lihat docs/11-panduan-deploy-dan-koneksi.md bagian 3)."
    fi
  fi

  kode=$(kode_http http://localhost:3000/)
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

# 03 — Setup & Docker

## A. Docker (direkomendasikan, paling mudah dirawat)
Prasyarat: Docker (atau **colima** di macOS).
```bash
colima start                 # khusus macOS tanpa Docker Desktop
docker compose up -d --build # app(PHP-FPM) + web(Nginx) + db(MySQL8) + adminer
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```
| Layanan | URL |
|---------|-----|
| Aplikasi | http://localhost:8080 |
| Adminer (GUI DB) | http://localhost:8081 |
| phpMyAdmin (GUI DB ala XAMPP) | http://localhost:8082 |
| MySQL (host) | 127.0.0.1:3307 |

Build asset (Bootstrap 5 + Alpine via Vite):
```bash
docker compose --profile assets up node   # sekali jalan: npm install && npm run build
```

### Perintah harian
```bash
docker compose exec app php artisan migrate:fresh --seed   # reset data demo
docker compose exec app php artisan tinker                 # REPL
docker compose logs -f web app                             # lihat log
docker compose down                                        # stop (data tetap di volume)
```

## B. Lokal (tanpa Docker)
Prasyarat: PHP 8.3, Composer, MySQL 8. Set `.env` DB_HOST/PORT sesuai MySQL lokal, lalu:
```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://127.0.0.1:8000
```

## Konteks host vs container
`.env` diset untuk **host** (`DB_HOST=127.0.0.1`, `DB_PORT=3307`). Di dalam container,
`docker-compose.yml` meng-override ke `DB_HOST=db`, `DB_PORT=3306`. Jadi satu `.env` jalan di dua tempat.

# Image PHP-FPM 8.3 untuk Redline Komputer (SIRC)
FROM php:8.3-fpm-alpine

# Dependensi sistem + ekstensi PHP yang dibutuhkan Laravel + dompdf
RUN apk add --no-cache \
        git curl libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev oniguruma-dev mysql-client bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath gd zip intl exif \
    && rm -rf /var/cache/apk/*

# Composer (dari image resmi)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Jalankan sebagai user non-root untuk keamanan
RUN addgroup -g 1000 redline && adduser -G redline -u 1000 -s /bin/bash -D redline

WORKDIR /var/www

# Entrypoint: siapkan izin & tunggu DB sebelum FPM start
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER redline

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

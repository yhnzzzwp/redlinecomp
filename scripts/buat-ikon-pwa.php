<?php

declare(strict_types=1);

/*
 * Generator ikon PWA — dijalankan sekali secara lokal, hasilnya di-commit.
 *
 *   php scripts/buat-ikon-pwa.php /path/ke/barlow-condensed-800-italic.ttf
 *
 * Font TTF diturunkan dari font brand yang sudah self-host di public/fonts
 * (woff2 → ttf, mis. via `fonttools ttLib` + brotli). Tidak ada aset atau
 * layanan pihak ketiga baru: hanya PHP GD + font milik proyek sendiri.
 *
 * Keluaran (public/icons/):
 *   pwa-192.png            ikon standar 192×192
 *   pwa-512.png            ikon standar 512×512
 *   pwa-maskable-512.png   varian maskable (konten di dalam zona aman 80%)
 *   apple-touch-icon.png   180×180 untuk perangkat Apple
 *
 * Desain mengikuti design system v2 "Instrument Panel":
 * monogram RL (Barlow Condensed 800 Italic, "L" merah) di atas carbon-950,
 * plus bar merah bercelah miring — motif .rl-logo::after di app.css.
 */

const CARBON = [0x0b, 0x0d, 0x11]; // --carbon-950
const RED = [0xde, 0x1f, 0x26];    // --red
const PUTIH = [0xff, 0xff, 0xff];

const SKALA_SUPERSAMPLE = 4; // gambar 4× lalu diperkecil supaya tepi mulus

if (! extension_loaded('gd') || ! function_exists('imagettftext')) {
    fwrite(STDERR, "Butuh ekstensi GD dengan dukungan FreeType.\n");
    exit(1);
}

$font = $argv[1] ?? null;
if ($font === null || ! is_file($font)) {
    fwrite(STDERR, "Pakai: php scripts/buat-ikon-pwa.php /path/barlow-condensed-800-italic.ttf\n");
    exit(1);
}

$tujuan = dirname(__DIR__).'/public/icons';
if (! is_dir($tujuan)) {
    mkdir($tujuan, 0755, true);
}

/**
 * Gambar satu ikon.
 *
 * @param float $skalaKonten 1.0 = normal; < 1.0 memuat konten ke zona aman (maskable)
 */
function gambarIkon(string $font, int $ukuran, float $skalaKonten): GdImage
{
    $s = $ukuran * SKALA_SUPERSAMPLE; // kanvas kerja
    $img = imagecreatetruecolor($s, $s);

    $carbon = imagecolorallocate($img, ...CARBON);
    $merah = imagecolorallocate($img, ...RED);
    $putih = imagecolorallocate($img, ...PUTIH);
    imagefilledrectangle($img, 0, 0, $s, $s, $carbon);

    // Seluruh koordinat didesain pada bidang 512, lalu diskala;
    // offset supaya konten yang diskala (maskable) tetap di tengah.
    $geser = ($s - $s * $skalaKonten) / 2;
    $k = fn (float $v): float => $v * $s / 512 * $skalaKonten + $geser;

    // ---- Monogram "RL" (R putih, L merah — meniru logo REDL|INE) ----
    $fontSize = 175.0 * $s / 512 * $skalaKonten; // pt; cap-height ± 232px pada bidang 512
    $baseline = $k(316);

    $bbR = imagettfbbox($fontSize, 0, $font, 'R');
    $bbL = imagettfbbox($fontSize, 0, $font, 'L');
    $lebarR = $bbR[2] - $bbR[0];
    $lebarL = $bbL[2] - $bbL[0];
    $celah = 6 * $s / 512 * $skalaKonten;
    $lebarTotal = $lebarR + $celah + $lebarL;

    $xAwal = ($s - $lebarTotal) / 2;
    imagettftext($img, $fontSize, 0, (int) round($xAwal), (int) round($baseline), $putih, $font, 'R');
    imagettftext($img, $fontSize, 0, (int) round($xAwal + $lebarR + $celah), (int) round($baseline), $merah, $font, 'L');

    // ---- Bar kecepatan: merah penuh dengan celah miring (motif .rl-logo::after) ----
    $yAtas = $k(354);
    $yBawah = $k(392);
    $xKiri = $k(118);
    $xKanan = $k(394);
    $miring = ($yBawah - $yAtas) * tan(deg2rad(18)); // kemiringan 18° khas design system

    $parallelogram = function (float $x1, float $x2) use ($img, $merah, $yAtas, $yBawah, $miring): void {
        imagefilledpolygon($img, [
            (int) round($x1 + $miring), (int) round($yAtas),
            (int) round($x2 + $miring), (int) round($yAtas),
            (int) round($x2), (int) round($yBawah),
            (int) round($x1), (int) round($yBawah),
        ], $merah);
    };
    $lebarBar = $xKanan - $xKiri;
    $parallelogram($xKiri, $xKiri + $lebarBar * 0.70);            // segmen kiri 0–70%
    $parallelogram($xKiri + $lebarBar * 0.78, $xKanan);           // segmen kanan 78–100%

    // Perkecil ke ukuran final (anti-alias via resampling).
    $final = imagecreatetruecolor($ukuran, $ukuran);
    imagecopyresampled($final, $img, 0, 0, 0, 0, $ukuran, $ukuran, $s, $s);

    return $final;
}

$daftar = [
    ['pwa-192.png', 192, 1.0],
    ['pwa-512.png', 512, 1.0],
    ['pwa-maskable-512.png', 512, 0.72], // konten masuk zona aman lingkaran 80%
    ['apple-touch-icon.png', 180, 1.0],
];

foreach ($daftar as [$nama, $ukuran, $skala]) {
    imagepng(gambarIkon($font, $ukuran, $skala), $tujuan.'/'.$nama, 9);
    echo "OK  public/icons/{$nama}\n";
}

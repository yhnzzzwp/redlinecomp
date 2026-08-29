<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Pegawai;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rotasi kredensial pegawai.
 *
 * Dibuat karena seeder memakai Hash::make('password') untuk seluruh akun dan
 * repositori proyek bersifat publik — kombinasi username/password itu harus
 * dianggap sudah tersebar. Menutup celah di kode tidak mencabut kredensial
 * yang terlanjur diketahui orang.
 *
 * Password dibuat acak oleh perintah ini, BUKAN diketik sebagai argumen,
 * supaya tidak tertinggal di riwayat shell.
 */
final class GantiPasswordPegawai extends Command
{
    protected $signature = 'redline:ganti-password
                            {username? : Username pegawai. Kosongkan bila memakai --semua}
                            {--semua : Ganti password SELURUH pegawai}
                            {--panjang=16 : Panjang password yang dibuat}';

    protected $description = 'Terbitkan password acak baru dan cabut seluruh sesi web serta token API pegawai';

    public function handle(): int
    {
        $semua = (bool) $this->option('semua');
        $username = $this->argument('username');

        if (! $semua && ! $username) {
            $this->error('Sebutkan username pegawai, atau gunakan --semua.');
            $this->line('Contoh: php artisan redline:ganti-password owner');
            $this->line('        php artisan redline:ganti-password --semua');

            return self::FAILURE;
        }

        $query = Pegawai::query();
        if (! $semua) {
            $query->where('username', $username);
        }

        $daftar = $query->orderBy('id')->get();

        if ($daftar->isEmpty()) {
            $this->error($semua ? 'Tidak ada pegawai terdaftar.' : "Pegawai \"{$username}\" tidak ditemukan.");

            return self::FAILURE;
        }

        $panjang = max(12, (int) $this->option('panjang'));
        $baris = [];

        foreach ($daftar as $pegawai) {
            $password = Str::password($panjang, symbols: false);

            // Cast 'hashed' pada model Pegawai yang melakukan hashing.
            $pegawai->password = $password;

            // Cookie "Ingat perangkat" hidup lebih lama daripada sesi; tanpa
            // rotasi ini perangkat lama bisa membuat sesi baru sendiri.
            $pegawai->setRememberToken(Str::random(60));
            $pegawai->save();

            $jumlahToken = $pegawai->apiTokens()->delete();
            $jumlahSesi = DB::table('sessions')->where('user_id', $pegawai->id)->delete();

            $baris[] = [
                $pegawai->username,
                $pegawai->role->value ?? (string) $pegawai->role,
                $password,
                $jumlahToken,
                $jumlahSesi,
            ];
        }

        $this->newLine();
        $this->table(
            ['Username', 'Role', 'Password Baru', 'Token API Dicabut', 'Sesi Web Dihapus'],
            $baris
        );

        $this->warn('Catat password di atas SEKARANG — tidak disimpan di mana pun dan tidak bisa ditampilkan ulang.');
        $this->line('Bagikan ke masing-masing pegawai lewat kanal pribadi, jangan lewat grup.');

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur 2FA TOTP Owner dihapus atas keputusan Owner (27 Jul 2026) —
 * kolom secret & kode pemulihan tidak dipakai lagi dan berisi rahasia,
 * jadi di-drop (bukan dibiarkan yatim).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table): void {
            $table->dropColumn(['totp_secret', 'totp_recovery']);
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table): void {
            $table->text('totp_secret')->nullable();
            $table->text('totp_recovery')->nullable();
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 2FA TOTP untuk Owner (portal admin): secret terenkripsi + kode pemulihan (hash). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table): void {
            $table->text('totp_secret')->nullable();
            $table->text('totp_recovery')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table): void {
            $table->dropColumn(['totp_secret', 'totp_recovery']);
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service', function (Blueprint $table): void {

            $table->foreignId('perangkat_id')
                  ->nullable()
                  ->after('nomor_resi')
                  ->constrained('perangkat')
                  ->nullOnDelete();

            $table->date('tanggal_selesai')->nullable()->after('estimasi_selesai');
            $table->text('catatan_solusi')->nullable()->after('masalah');

            $table->renameColumn('masalah', 'keluhan');

            $table->dropColumn(['nama_customer', 'nomor_hp_customer', 'nama_barang']);
        });
    }

    public function down(): void
    {
        Schema::table('service', function (Blueprint $table): void {

            $table->string('nama_barang')->after('nomor_hp_customer');
            $table->string('nomor_hp_customer')->nullable()->after('nama_customer');
            $table->string('nama_customer')->after('pegawai_id');

            $table->renameColumn('keluhan', 'masalah');

            $table->dropColumn(['tanggal_selesai', 'catatan_solusi']);
            $table->dropForeign(['perangkat_id']);
            $table->dropColumn('perangkat_id');
        });
    }
};

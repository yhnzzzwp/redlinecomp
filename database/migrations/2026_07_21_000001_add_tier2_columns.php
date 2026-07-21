<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->unsignedBigInteger('harga_modal')->default(0)->after('harga');
        });

        Schema::table('promo', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable()->after('waktu_berakhir');
            $table->unsignedInteger('terpakai')->default(0)->after('kuota');
        });

        Schema::table('service', function (Blueprint $table) {
            $table->foreignId('teknisi_id')->nullable()->after('pegawai_id')->constrained('pegawai')->nullOnDelete();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('status')->default('Normal')->after('kembalian');
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn('harga_modal');
        });

        Schema::table('promo', function (Blueprint $table) {
            $table->dropColumn(['kuota', 'terpakai']);
        });

        Schema::table('service', function (Blueprint $table) {
            $table->dropForeign(['teknisi_id']);
            $table->dropColumn('teknisi_id');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

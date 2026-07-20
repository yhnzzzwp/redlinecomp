<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_nota')->unique();
            $table->foreignId('pegawai_id')->constrained('pegawai');
            $table->foreignId('promo_id')->nullable()->constrained('promo')->nullOnDelete();
            $table->string('metode_bayar')->default('Tunai');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('diskon')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('bayar')->default(0);
            $table->bigInteger('kembalian')->default(0);
            $table->string('nama_pembeli')->nullable();
            $table->string('nomor_hp_pembeli')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('transaksi'); }
};

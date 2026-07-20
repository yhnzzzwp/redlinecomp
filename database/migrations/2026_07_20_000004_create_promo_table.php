<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promo', function (Blueprint $table) {
            $table->id();
            $table->string('nama_promo');
            $table->string('kode_promo')->unique();
            $table->string('tipe_promo')->default('Persen');
            $table->unsignedBigInteger('besar_promo');
            $table->unsignedBigInteger('minimal_transaksi')->default(0);
            $table->unsignedBigInteger('maksimal_diskon')->nullable();
            $table->date('waktu_mulai');
            $table->date('waktu_berakhir');
            $table->boolean('aktif')->default(true);
            $table->string('foto_promo')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('promo'); }
};

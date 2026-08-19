<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }

    public function down(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $table->string('tipe');
            $table->integer('jumlah_sebelum');
            $table->integer('selisih');
            $table->integer('jumlah_sesudah');
            $table->string('keterangan')->nullable();
            $table->foreignId('pegawai_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $table->timestamps();

            $table->index(['produk_id', 'created_at']);
            $table->index('tipe');
        });
    }
};

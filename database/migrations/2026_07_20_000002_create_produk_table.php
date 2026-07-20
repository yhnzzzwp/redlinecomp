<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_produk')->nullOnDelete();
            $table->string('sku')->unique()->nullable();
            $table->string('nama_produk');
            $table->unsignedInteger('jumlah_produk')->default(0);
            $table->unsignedBigInteger('harga')->default(0);
            $table->text('deskripsi_produk')->nullable();
            $table->boolean('show_katalog')->default(true);
            $table->string('foto_produk')->nullable();
            $table->timestamps();
            $table->index('nama_produk');
        });
    }
    public function down(): void { Schema::dropIfExists('produk'); }
};

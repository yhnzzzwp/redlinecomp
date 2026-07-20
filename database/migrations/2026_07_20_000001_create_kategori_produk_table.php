<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kategori_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->text('deskripsi_kategori')->nullable();
            $table->boolean('tampil_filter')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('kategori_produk'); }
};

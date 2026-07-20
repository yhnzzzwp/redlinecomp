<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->string('tipe')->default('Produk');
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service')->nullOnDelete();
            $table->string('nama_item');
            $table->unsignedInteger('jumlah')->default(1);
            $table->unsignedBigInteger('harga')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('item_transaksi'); }
};

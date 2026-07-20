<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('part_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('service')->cascadeOnDelete();
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();
            $table->string('nama_part');
            $table->unsignedInteger('jumlah')->default(1);
            $table->unsignedBigInteger('harga')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('part_service'); }
};

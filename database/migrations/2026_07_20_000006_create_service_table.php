<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_resi')->unique();
            $table->foreignId('pegawai_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $table->string('nama_customer');
            $table->string('nomor_hp_customer')->nullable();
            $table->string('nama_barang');
            $table->text('masalah');
            $table->unsignedBigInteger('biaya_service')->default(0);
            $table->string('status')->default('Diterima');
            $table->date('tanggal_masuk')->nullable();
            $table->date('estimasi_selesai')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('service'); }
};

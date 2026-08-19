<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_perangkat')->unique();
            $table->string('nama_customer');
            $table->string('nomor_hp_customer')->nullable();
            $table->string('merk_model');
            $table->string('serial_number')->nullable();
            $table->string('tahun')->nullable();
            $table->text('spesifikasi')->nullable();
            $table->timestamps();

            $table->index('nama_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat');
    }
};

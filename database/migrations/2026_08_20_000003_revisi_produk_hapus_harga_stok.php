<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table): void {
            $table->dropColumn(['harga', 'harga_modal', 'jumlah_produk']);
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table): void {
            $table->unsignedBigInteger('harga')->default(0)->after('nama_produk');
            $table->unsignedBigInteger('harga_modal')->default(0)->after('harga');
            $table->unsignedInteger('jumlah_produk')->default(0)->after('nama_produk');
        });
    }
};

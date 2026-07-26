<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aplikasi tidak lagi menampilkan maupun mengunggah gambar (keputusan Owner):
 * kolom foto pada produk & promo dihapus dari skema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table): void {
            $table->dropColumn('foto_produk');
        });

        Schema::table('promo', function (Blueprint $table): void {
            $table->dropColumn('foto_promo');
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table): void {
            $table->string('foto_produk')->nullable();
        });

        Schema::table('promo', function (Blueprint $table): void {
            $table->string('foto_promo')->nullable();
        });
    }
};

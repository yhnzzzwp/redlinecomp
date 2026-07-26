<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Snapshot harga modal saat transaksi terjadi — supaya HPP di jurnal
 * akuntansi & laporan tidak berubah retroaktif ketika harga_modal produk
 * diedit atau produknya dihapus (pola sama dengan snapshot harga/subtotal).
 *
 *  - item_transaksi.harga_modal : modal per unit saat checkout
 *    (item Servis: total modal part per unit servis).
 *  - part_service.harga_modal   : modal per unit part saat part dipasang.
 *
 * Nullable: baris lama (sebelum migrasi) tidak punya snapshot — jurnal
 * memakai fallback harga_modal produk saat ekspor (didokumentasikan
 * di sheet Info).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table): void {
            $table->unsignedBigInteger('harga_modal')->nullable()->after('harga');
        });
        Schema::table('part_service', function (Blueprint $table): void {
            $table->unsignedBigInteger('harga_modal')->nullable()->after('harga');
        });
    }

    public function down(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table): void {
            $table->dropColumn('harga_modal');
        });
        Schema::table('part_service', function (Blueprint $table): void {
            $table->dropColumn('harga_modal');
        });
    }
};

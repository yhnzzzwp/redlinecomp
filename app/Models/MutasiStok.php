<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipeMutasiStok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stok';

    protected $fillable = [
        'produk_id', 'tipe', 'jumlah_sebelum', 'selisih', 'jumlah_sesudah',
        'keterangan', 'pegawai_id',
    ];

    protected $casts = [
        'tipe' => TipeMutasiStok::class,
        'jumlah_sebelum' => 'integer',
        'selisih' => 'integer',
        'jumlah_sesudah' => 'integer',
    ];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }
}

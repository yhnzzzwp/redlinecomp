<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'kode_nota', 'local_id', 'pegawai_id', 'promo_id', 'metode_bayar',
        'subtotal', 'diskon', 'total', 'bayar', 'kembalian',
        'nama_pembeli', 'nomor_hp_pembeli', 'status',
    ];

    protected $casts = [
        'subtotal' => 'integer', 'diskon' => 'integer', 'total' => 'integer',
        'bayar' => 'integer', 'kembalian' => 'integer',
        'status' => \App\Enums\TransaksiStatus::class,
    ];

    public function pegawai(): BelongsTo { return $this->belongsTo(Pegawai::class, 'pegawai_id'); }
    public function promo(): BelongsTo { return $this->belongsTo(Promo::class, 'promo_id'); }
    public function items(): HasMany { return $this->hasMany(ItemTransaksi::class, 'transaksi_id'); }
}

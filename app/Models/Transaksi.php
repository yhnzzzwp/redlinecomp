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
        // metode_bayar sebelumnya tidak di-cast, sehingga setiap
        // $t->metode_bayar->value di controller API membaca properti pada
        // sebuah string dan menghasilkan null — metode bayar pada respons API
        // dan nota digital selama ini kosong.
        'metode_bayar' => \App\Enums\MetodeBayar::class,
    ];

    /** @return BelongsTo<Pegawai, $this> */
    public function pegawai(): BelongsTo { return $this->belongsTo(Pegawai::class, 'pegawai_id'); }
    /** @return BelongsTo<Promo, $this> */
    public function promo(): BelongsTo { return $this->belongsTo(Promo::class, 'promo_id'); }
    /** @return HasMany<ItemTransaksi, $this> */
    public function items(): HasMany { return $this->hasMany(ItemTransaksi::class, 'transaksi_id'); }
}

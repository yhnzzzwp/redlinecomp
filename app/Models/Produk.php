<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kategori_id', 'sku', 'nama_produk',
        'deskripsi_produk', 'show_katalog',
    ];

    protected $casts = [
        'show_katalog' => 'boolean',
    ];

    /** @return BelongsTo<KategoriProduk, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_id');
    }
}

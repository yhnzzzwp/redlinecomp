<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kategori_id', 'sku', 'nama_produk', 'jumlah_produk',
        'harga', 'harga_modal', 'deskripsi_produk', 'show_katalog',
    ];

    protected $casts = [
        'show_katalog' => 'boolean',
        'harga' => 'integer',
        'harga_modal' => 'integer',
        'jumlah_produk' => 'integer',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_id');
    }

    public function statusStok(): string
    {
        if ($this->jumlah_produk <= 0) return 'Out of Stock';
        if ($this->jumlah_produk <= config('redline.stok_kritis')) return 'Low Stock';
        return 'In Stock';
    }
}

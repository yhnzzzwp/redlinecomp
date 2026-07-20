<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriProduk extends Model
{
    protected $table = 'kategori_produk';

    protected $fillable = ['nama_kategori', 'deskripsi_kategori', 'tampil_filter'];

    protected $casts = ['tampil_filter' => 'boolean'];

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }
}

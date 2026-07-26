<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipeItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTransaksi extends Model
{
    protected $table = 'item_transaksi';
    public $timestamps = false;

    protected $fillable = [
        'transaksi_id', 'tipe', 'produk_id', 'service_id',
        'nama_item', 'jumlah', 'harga', 'harga_modal', 'subtotal',
    ];

    protected $casts = [
        'tipe' => TipeItem::class,
        'jumlah' => 'integer', 'harga' => 'integer', 'subtotal' => 'integer',
    ];

    public function transaksi(): BelongsTo { return $this->belongsTo(Transaksi::class, 'transaksi_id'); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class, 'service_id'); }
}

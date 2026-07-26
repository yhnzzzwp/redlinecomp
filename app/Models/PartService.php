<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartService extends Model
{
    protected $table = 'part_service';
    public $timestamps = false;

    protected $fillable = ['service_id', 'produk_id', 'nama_part', 'jumlah', 'harga', 'harga_modal', 'subtotal'];

    protected $casts = ['jumlah' => 'integer', 'harga' => 'integer', 'harga_modal' => 'integer', 'subtotal' => 'integer'];

    public function service(): BelongsTo { return $this->belongsTo(Service::class, 'service_id'); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
}

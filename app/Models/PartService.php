<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartService extends Model
{
    protected $table = 'part_service';
    public $timestamps = false;

    protected $fillable = ['service_id', 'produk_id', 'nama_part', 'jumlah', 'harga', 'subtotal'];

    protected $casts = ['jumlah' => 'integer', 'harga' => 'integer', 'subtotal' => 'integer'];

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo { return $this->belongsTo(Service::class, 'service_id'); }
    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
}

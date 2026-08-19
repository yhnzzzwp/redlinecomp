<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $table = 'service';

    protected $fillable = [
        'nomor_resi', 'perangkat_id', 'pegawai_id', 'teknisi_id',
        'keluhan', 'catatan_solusi', 'biaya_service', 'status',
        'tanggal_masuk', 'estimasi_selesai', 'tanggal_selesai',
    ];

    protected $casts = [
        'status' => StatusService::class,
        'biaya_service' => 'integer',
        'tanggal_masuk' => 'date',
        'estimasi_selesai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function perangkat(): BelongsTo { return $this->belongsTo(Perangkat::class, 'perangkat_id'); }
    public function pegawai(): BelongsTo { return $this->belongsTo(Pegawai::class, 'pegawai_id'); }
    public function teknisi(): BelongsTo { return $this->belongsTo(Pegawai::class, 'teknisi_id'); }
    public function parts(): HasMany { return $this->hasMany(PartService::class, 'service_id'); }
    public function riwayat(): HasMany { return $this->hasMany(ServiceStatus::class, 'service_id')->latest(); }

    public function totalBiaya(): int
    {
        $part = $this->relationLoaded('parts')
            ? (int) $this->parts->sum('subtotal')
            : (int) $this->parts()->sum('subtotal');

        return (int) $this->biaya_service + $part;
    }
}

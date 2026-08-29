<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusService;
use App\Support\Resi;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Cari tiket berdasarkan nomor resi yang diketik ulang manusia.
     *
     * Cocokkan persis lebih dulu supaya indeks kolom terpakai untuk kasus
     * normal; perbandingan kanonik hanya dipakai sebagai jaring pengaman bagi
     * masukan yang pemisahnya berbeda ("PK 2026 00 01", "pk20260001").
     *
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeResiSetara(Builder $query, string $resi): Builder
    {
        $kanonik = Resi::kanonik($resi);

        return $query->where(function (Builder $query) use ($resi, $kanonik): void {
            $query->where('nomor_resi', $resi)
                ->orWhereRaw(
                    "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(nomor_resi, '-', ''), ' ', ''), '_', ''), '.', '')) = ?",
                    [$kanonik],
                );
        });
    }

    // Anotasi generic dibutuhkan larastan untuk mengetahui model terkait;
    // tanpa ini setiap akses seperti $service->perangkat->merk_model dianggap
    // properti tak dikenal pada Eloquent\Model.

    /** @return BelongsTo<Perangkat, $this> */
    public function perangkat(): BelongsTo { return $this->belongsTo(Perangkat::class, 'perangkat_id'); }

    /** @return BelongsTo<Pegawai, $this> */
    public function pegawai(): BelongsTo { return $this->belongsTo(Pegawai::class, 'pegawai_id'); }

    /** @return BelongsTo<Pegawai, $this> */
    public function teknisi(): BelongsTo { return $this->belongsTo(Pegawai::class, 'teknisi_id'); }

    /** @return HasMany<PartService, $this> */
    public function parts(): HasMany { return $this->hasMany(PartService::class, 'service_id'); }

    /** @return HasMany<ServiceStatus, $this> */
    public function riwayat(): HasMany { return $this->hasMany(ServiceStatus::class, 'service_id')->latest(); }

    public function totalBiaya(): int
    {
        $part = $this->relationLoaded('parts')
            ? (int) $this->parts->sum('subtotal')
            : (int) $this->parts()->sum('subtotal');

        return (int) $this->biaya_service + $part;
    }
}

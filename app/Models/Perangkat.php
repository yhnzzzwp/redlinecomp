<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perangkat extends Model
{
    protected $table = 'perangkat';

    protected $fillable = [
        'kode_perangkat', 'nama_customer', 'nomor_hp_customer',
        'merk_model', 'serial_number', 'tahun', 'spesifikasi',
    ];

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'perangkat_id')->latest('tanggal_masuk');
    }
}

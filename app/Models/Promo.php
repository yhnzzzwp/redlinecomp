<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipePromo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    protected $table = 'promo';

    protected $fillable = [
        'nama_promo', 'kode_promo', 'tipe_promo', 'besar_promo',
        'minimal_transaksi', 'maksimal_diskon', 'waktu_mulai',
        'waktu_berakhir', 'aktif', 'kuota', 'terpakai',
    ];

    protected $casts = [
        'tipe_promo' => TipePromo::class,
        'aktif' => 'boolean',
        'waktu_mulai' => 'date',
        'waktu_berakhir' => 'date',
        'kuota' => 'integer',
        'terpakai' => 'integer',
    ];

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'promo_id');
    }

    public function sedangBerlaku(): bool
    {
        $today = now()->startOfDay();
        return $this->aktif
            && $this->waktu_mulai->lte($today)
            && $this->waktu_berakhir->gte($today)
            && $this->masihAdaKuota();
    }

    public function masihAdaKuota(): bool
    {
        if (is_null($this->kuota)) {
            return true;
        }

        return $this->terpakai < $this->kuota;
    }
}

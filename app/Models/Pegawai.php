<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolePegawai;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pegawai extends Authenticatable
{
    use Notifiable;

    protected $table = 'pegawai';

    protected $fillable = [
        'nama_pegawai', 'username', 'email', 'password', 'role',
        'nomor_hp', 'alamat_pegawai', 'tanggal_masuk', 'masih_bekerja',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'role' => RolePegawai::class,
        'masih_bekerja' => 'boolean',
        'tanggal_masuk' => 'date',
    ];

    public function isOwner(): bool
    {
        return $this->role === RolePegawai::Owner;
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'pegawai_id');
    }

    public function service(): HasMany
    {
        return $this->hasMany(Service::class, 'pegawai_id');
    }
}

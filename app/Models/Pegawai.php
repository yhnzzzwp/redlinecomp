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

    /** @return HasMany<Transaksi, $this> */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'pegawai_id');
    }

    /** @return HasMany<Service, $this> */
    public function service(): HasMany
    {
        return $this->hasMany(Service::class, 'pegawai_id');
    }

    /** @return HasMany<ApiToken, $this> */
    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class, 'pegawai_id');
    }

    public function createApiToken(string $name = 'default', ?array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): string
    {
        $plainToken = 'rl_tok_' . \Illuminate\Support\Str::random(40) . '_' . dechex((int) microtime(true));

        // Token WAJIB punya masa berlaku. Sebelumnya $expiresAt default null
        // dan tidak ada pemanggil yang mengisinya, sehingga setiap token yang
        // pernah diterbitkan berlaku selamanya — satu kebocoran berarti akses
        // permanen. EnsureApiAuthenticated sudah memeriksa isExpired().
        $this->apiTokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt ?? now()->addDays((int) config('redline.token_ttl_days', 30)),
        ]);

        return $plainToken;
    }
}

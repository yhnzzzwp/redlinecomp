<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceStatus extends Model
{
    protected $table = 'service_status';

    protected $fillable = ['service_id', 'pegawai_id', 'status', 'catatan'];

    protected $casts = ['status' => StatusService::class];

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo { return $this->belongsTo(Service::class, 'service_id'); }
    /** @return BelongsTo<Pegawai, $this> */
    public function pegawai(): BelongsTo { return $this->belongsTo(Pegawai::class, 'pegawai_id'); }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusService;
use App\Models\PartService;
use App\Models\Pegawai;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

final class ServiceTicketService
{
    public function __construct(private readonly KodeGenerator $kodeGenerator) {}

    public function buat(array $data, Pegawai $pegawai): Service
    {
        return DB::transaction(function () use ($data, $pegawai): Service {
            $service = Service::query()->create([
                'nomor_resi' => $this->kodeGenerator->resi(),
                'pegawai_id' => $pegawai->id,
                'nama_customer' => $data['nama_customer'],
                'nomor_hp_customer' => $data['nomor_hp_customer'] ?? null,
                'nama_barang' => $data['nama_barang'],
                'masalah' => $data['masalah'],
                'biaya_service' => $data['biaya_service'] ?? 0,
                'status' => StatusService::Diterima,
                'tanggal_masuk' => now(),
                'estimasi_selesai' => $data['estimasi_selesai'] ?? null,
            ]);

            $service->riwayat()->create([
                'pegawai_id' => $pegawai->id,
                'status' => StatusService::Diterima,
                'catatan' => 'Unit diterima dan dicatat.',
            ]);

            return $service;
        });
    }

    public function updateStatus(Service $service, StatusService $status, ?string $catatan, Pegawai $pegawai): Service
    {
        return DB::transaction(function () use ($service, $status, $catatan, $pegawai): Service {
            $service->update(['status' => $status]);

            $service->riwayat()->create([
                'pegawai_id' => $pegawai->id,
                'status' => $status,
                'catatan' => $catatan,
            ]);

            return $service;
        });
    }

    public function tambahPart(Service $service, array $data): PartService
    {
        $jumlah = (int) $data['jumlah'];
        $harga = (int) $data['harga'];

        $part = new PartService([
            'produk_id' => $data['produk_id'] ?? null,
            'nama_part' => $data['nama_part'],
            'jumlah' => $jumlah,
            'harga' => $harga,
            'subtotal' => $jumlah * $harga,
        ]);

        $service->parts()->save($part);

        return $part;
    }
}

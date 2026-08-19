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
    public function __construct(
        private readonly KodeGenerator $kodeGenerator,
        private readonly StokService $stok,
    ) {}

    public function buat(array $data, Pegawai $pegawai): Service
    {
        return DB::transaction(function () use ($data, $pegawai): Service {

            $service = \App\Support\CobaUlang::unik(fn (): Service => Service::query()->create([
                'nomor_resi' => $this->kodeGenerator->resi(),
                'pegawai_id' => $pegawai->id,
                'perangkat_id' => $data['perangkat_id'],
                'keluhan' => $data['keluhan'],
                'biaya_service' => $data['biaya_service'] ?? 0,
                'status' => StatusService::Diterima,
                'tanggal_masuk' => now(),
                'estimasi_selesai' => $data['estimasi_selesai'] ?? null,
                'teknisi_id' => $data['teknisi_id'] ?? null,
            ]));

            $service->riwayat()->create([
                'pegawai_id' => $pegawai->id,
                'status' => StatusService::Diterima,
                'catatan' => 'Unit diterima dan dicatat.',
            ]);

            return $service;
        });
    }

    public function updateStatus(Service $service, StatusService $status, ?string $catatan, Pegawai $pegawai): array
    {
        if (! $service->status->canTransitionTo($status)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => "Tidak bisa mengubah status dari \"{$service->status->value}\" ke \"{$status->value}\".",
            ]);
        }

        return DB::transaction(function () use ($service, $status, $catatan, $pegawai): array {
            $service->update(['status' => $status]);

            $service->riwayat()->create([
                'pegawai_id' => $pegawai->id,
                'status' => $status,
                'catatan' => $catatan,
            ]);

            return [$service, \App\Support\Wa::linkStatusServis($service)];
        });
    }

    public function tambahPart(Service $service, array $data): PartService
    {
        return DB::transaction(function () use ($service, $data): PartService {
            $jumlah = (int) $data['jumlah'];
            $harga = (int) $data['harga'];
            $produkId = isset($data['produk_id']) && $data['produk_id'] !== '' ? (int) $data['produk_id'] : null;

            $produk = null;
            if ($produkId !== null) {
                $produk = \App\Models\Produk::query()->lockForUpdate()->find($produkId);
            }

            if ($produk === null && ! empty($data['nama_part'])) {
                $produk = \App\Models\Produk::query()
                    ->where('nama_produk', trim((string) $data['nama_part']))
                    ->lockForUpdate()
                    ->first();
            }

            if ($produk !== null) {
                if ($produk->jumlah_produk < $jumlah) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'jumlah' => "Stok produk \"{$produk->nama_produk}\" tidak mencukupi (Sisa stok: {$produk->jumlah_produk}).",
                    ]);
                }
                $sebelum = (int) $produk->jumlah_produk;
                $produk->decrement('jumlah_produk', $jumlah);
                $this->stok->catat(
                    $produk, $sebelum, $sebelum - $jumlah,
                    \App\Enums\TipeMutasiStok::PartServis,
                    'Part servis '.$service->nomor_resi,
                );
                $produkId = $produk->id;
            }

            $part = new PartService([
                'produk_id' => $produkId,
                'nama_part' => $data['nama_part'],
                'jumlah' => $jumlah,
                'harga' => $harga,

                'harga_modal' => $produk !== null ? (int) $produk->harga_modal : null,
                'subtotal' => $jumlah * $harga,
            ]);

            $service->parts()->save($part);

            return $part;
        });
    }

    public function hapusPart(Service $service, PartService $part): void
    {
        DB::transaction(function () use ($service, $part): void {
            if ($part->produk_id !== null) {
                $produk = \App\Models\Produk::query()->lockForUpdate()->find($part->produk_id);
                if ($produk !== null) {
                    $sebelum = (int) $produk->jumlah_produk;
                    $produk->increment('jumlah_produk', $part->jumlah);
                    $this->stok->catat(
                        $produk, $sebelum, $sebelum + $part->jumlah,
                        \App\Enums\TipeMutasiStok::PartServis,
                        'Part servis dibatalkan '.$service->nomor_resi,
                    );
                }
            }
            $part->delete();
        });
    }
}

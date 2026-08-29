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

            // Pelacakan stok sudah dihapus dari sistem: migrasi 2026_08_20_000003
            // menghapus kolom jumlah_produk/harga_modal dari tabel produk dan
            // 2026_08_20_000008 men-drop tabel mutasi_stok.
            //
            // Kode lama di sini masih memeriksa $produk->jumlah_produk. Karena
            // kolomnya tidak ada, nilainya null dan "null < 1" selalu bernilai
            // true di PHP — sehingga SETIAP penambahan sparepart gagal dengan
            // pesan "Sisa stok: " yang kosong. Andai lolos pun, baris di
            // bawahnya memanggil StokService::catat() yang kini kelas kosong,
            // dan itu fatal error.
            if ($produk !== null) {
                $produkId = $produk->id;
            }

            $part = new PartService([
                'produk_id' => $produkId,
                'nama_part' => $data['nama_part'],
                'jumlah' => $jumlah,
                'harga' => $harga,
                'subtotal' => $jumlah * $harga,
            ]);

            $service->parts()->save($part);

            return $part;
        });
    }

    public function hapusPart(Service $service, PartService $part): void
    {
        DB::transaction(function () use ($part): void {
            // Tidak ada lagi stok yang perlu dikembalikan; lihat catatan di
            // tambahPart(). increment('jumlah_produk') pada kolom yang sudah
            // dihapus akan menghasilkan galat SQL.
            $part->delete();
        });
    }
}

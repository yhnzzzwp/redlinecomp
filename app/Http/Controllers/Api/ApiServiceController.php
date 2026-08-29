<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perangkat;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiServiceController extends Controller
{
    public function cek(Request $request): JsonResponse
    {
        $resi = trim((string) $request->query('resi'));

        if ($resi === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor resi wajib diisi',
            ], 422);
        }

        $service = Service::query()
            ->with(['perangkat', 'parts', 'riwayat.pegawai'])
            ->where('nomor_resi', $resi)
            ->first();

        if (! $service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket servis tidak ditemukan',
            ], 404);
        }

        $maskedPhone = \App\Support\Privasi::nomorHp($service->perangkat?->nomor_hp_customer);

        return response()->json([
            'status' => 'success',
            'data' => [
                'nomor_resi' => $service->nomor_resi,
                'status' => $service->status->value,
                'status_warna' => $service->status->warna(),
                'merk_model' => $service->perangkat?->merk_model,
                'nama_customer' => \App\Support\Privasi::namaSingkat($service->perangkat?->nama_customer),
                'nomor_hp_customer' => $maskedPhone,
                'keluhan' => $service->keluhan,
                'catatan_solusi' => $service->catatan_solusi,
                'tanggal_masuk' => $service->tanggal_masuk?->format('Y-m-d'),
                'estimasi_selesai' => $service->estimasi_selesai?->format('Y-m-d'),
                'tanggal_selesai' => $service->tanggal_selesai?->format('Y-m-d'),
                'biaya_service' => (int) $service->biaya_service,
                'biaya_parts' => (int) $service->parts->sum('subtotal'),
                'total_biaya' => $service->totalBiaya(),
                'parts' => $service->parts->map(fn ($p) => [
                    'nama_part' => $p->nama_part,
                    'jumlah' => $p->jumlah,
                    'harga' => $p->harga,
                    'subtotal' => $p->subtotal,
                ]),
                'riwayat' => $service->riwayat->map(fn ($r) => [
                    'status' => $r->status->value,
                    'status_warna' => $r->status->warna(),
                    'catatan' => $r->catatan,
                    'waktu' => $r->created_at?->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }

    public function perangkat(string $kode): JsonResponse
    {
        $perangkat = Perangkat::query()
            ->with(['services.parts', 'services.riwayat'])
            ->where('kode_perangkat', $kode)
            ->first();

        if (! $perangkat) {
            return response()->json([
                'status' => 'error',
                'message' => 'Perangkat dengan kode QR tersebut tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $perangkat->id,
                'kode_perangkat' => $perangkat->kode_perangkat,
                // Endpoint ini publik — nama dan nomor pelanggan disamarkan
                // supaya kode perangkat yang bocor/ditebak tidak berubah
                // menjadi alat pemanen daftar pelanggan.
                'nama_customer' => \App\Support\Privasi::namaSingkat($perangkat->nama_customer),
                'nomor_hp_customer' => \App\Support\Privasi::nomorHp($perangkat->nomor_hp_customer),
                'merk_model' => $perangkat->merk_model,
                'serial_number' => $perangkat->serial_number,
                'tahun' => $perangkat->tahun,
                'spesifikasi' => $perangkat->spesifikasi,
                'services' => $perangkat->services->map(fn ($s) => [
                    'id' => $s->id,
                    'nomor_resi' => $s->nomor_resi,
                    'status' => $s->status->value,
                    'status_warna' => $s->status->warna(),
                    'keluhan' => $s->keluhan,
                    'catatan_solusi' => $s->catatan_solusi,
                    'tanggal_masuk' => $s->tanggal_masuk?->format('Y-m-d'),
                    'tanggal_selesai' => $s->tanggal_selesai?->format('Y-m-d'),
                    'total_biaya' => $s->totalBiaya(),
                    'parts' => $s->parts->map(fn ($p) => [
                        'nama_part' => $p->nama_part,
                        'jumlah' => $p->jumlah,
                        'harga' => $p->harga,
                        'subtotal' => $p->subtotal,
                    ]),
                ]),
            ],
        ]);
    }
}

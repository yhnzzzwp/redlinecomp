<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\StatusService;
use App\Http\Controllers\Controller;
use App\Models\PartService;
use App\Models\Pegawai;
use App\Models\Perangkat;
use App\Models\Service;
use App\Services\KodeGenerator;
use App\Services\ServiceTicketService;
use App\Support\CobaUlang;
use App\Support\Wa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiServiceManagementController extends Controller
{
    public function __construct(
        private readonly ServiceTicketService $serviceTicketService,
        private readonly KodeGenerator $kodeGenerator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['perangkat', 'pegawai', 'teknisi', 'parts']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari) {
                $q->where('nomor_resi', 'like', "%{$cari}%")
                  ->orWhere('keluhan', 'like', "%{$cari}%")
                  ->orWhereHas('perangkat', function ($sub) use ($cari) {
                      $sub->where('nama_customer', 'like', "%{$cari}%")
                          ->orWhere('nomor_hp_customer', 'like', "%{$cari}%")
                          ->orWhere('merk_model', 'like', "%{$cari}%")
                          ->orWhere('kode_perangkat', 'like', "%{$cari}%");
                  });
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $services = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $services->map(fn (Service $s) => $this->formatServiceResponse($s)),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page'    => $services->lastPage(),
                'per_page'     => $services->perPage(),
                'total'        => $services->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'perangkat_id'       => ['nullable', 'exists:perangkat,id'],
            'nama_customer'      => ['required_without:perangkat_id', 'nullable', 'string', 'max:255'],
            'nomor_hp_customer'  => ['nullable', 'string', 'max:30'],
            'merk_model'         => ['required_without:perangkat_id', 'nullable', 'string', 'max:255'],
            'serial_number'      => ['nullable', 'string', 'max:100'],
            'tahun'              => ['nullable', 'string', 'max:10'],
            'spesifikasi'        => ['nullable', 'string'],
            'keluhan'            => ['required', 'string'],
            'biaya_service'      => ['nullable', 'integer', 'min:0'],
            'estimasi_selesai'   => ['nullable', 'date'],
            'teknisi_id'         => ['nullable', 'exists:pegawai,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data tiket servis tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /** @var Pegawai|null $pegawai */
        $pegawai = $request->user() ?? Pegawai::first();

        try {
            $service = DB::transaction(function () use ($request, $pegawai) {
                $perangkatId = $request->input('perangkat_id');

                if (empty($perangkatId)) {
                    $kodePerangkat = 'DEV-' . strtoupper(\Illuminate\Support\Str::random(8));
                    $perangkat = Perangkat::create([
                        'kode_perangkat'    => $kodePerangkat,
                        'nama_customer'     => (string) $request->input('nama_customer'),
                        'nomor_hp_customer' => $request->input('nomor_hp_customer'),
                        'merk_model'        => (string) $request->input('merk_model'),
                        'serial_number'     => $request->input('serial_number'),
                        'tahun'             => $request->input('tahun'),
                        'spesifikasi'       => $request->input('spesifikasi'),
                    ]);
                    $perangkatId = $perangkat->id;
                }

                $service = CobaUlang::unik(fn (): Service => Service::create([
                    'nomor_resi'       => $this->kodeGenerator->resi(),
                    'pegawai_id'       => $pegawai->id,
                    'perangkat_id'     => $perangkatId,
                    'keluhan'          => (string) $request->input('keluhan'),
                    'biaya_service'    => (int) $request->input('biaya_service', 0),
                    'status'           => StatusService::Diterima,
                    'tanggal_masuk'    => now(),
                    'estimasi_selesai' => $request->input('estimasi_selesai'),
                    'teknisi_id'       => $request->input('teknisi_id'),
                ]));

                $service->riwayat()->create([
                    'pegawai_id' => $pegawai->id,
                    'status'     => StatusService::Diterima,
                    'catatan'    => 'Unit diterima dan dicatat.',
                ]);

                return $service;
            });

            $service->load(['perangkat', 'pegawai', 'teknisi', 'parts', 'riwayat.pegawai']);

            return response()->json([
                'status'  => 'success',
                'message' => "Tiket servis berhasil dibuat. Resi #{$service->nomor_resi}",
                'data'    => $this->formatServiceResponse($service),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat tiket servis: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Service $service): JsonResponse
    {
        $service->load(['perangkat', 'pegawai', 'teknisi', 'parts', 'riwayat.pegawai']);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatServiceResponse($service),
        ]);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keluhan'          => ['required', 'string'],
            'catatan_solusi'   => ['nullable', 'string'],
            'biaya_service'    => ['required', 'integer', 'min:0'],
            'estimasi_selesai' => ['nullable', 'date'],
            'tanggal_selesai'  => ['nullable', 'date'],
            'teknisi_id'       => ['nullable', 'exists:pegawai,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data tiket servis tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $service->update([
            'keluhan'          => $request->input('keluhan'),
            'catatan_solusi'   => $request->input('catatan_solusi'),
            'biaya_service'    => (int) $request->input('biaya_service'),
            'estimasi_selesai' => $request->input('estimasi_selesai'),
            'tanggal_selesai'  => $request->input('tanggal_selesai'),
            'teknisi_id'       => $request->input('teknisi_id'),
        ]);

        $service->load(['perangkat', 'pegawai', 'teknisi', 'parts', 'riwayat.pegawai']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Tiket servis berhasil diperbarui.',
            'data'    => $this->formatServiceResponse($service),
        ]);
    }

    public function updateStatus(Request $request, Service $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status'  => ['required', Rule::enum(StatusService::class)],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Status servis tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /** @var Pegawai|null $pegawai */
        $pegawai = $request->user() ?? Pegawai::first();
        $targetStatus = StatusService::from((string) $request->input('status'));

        try {
            [$updatedService, $waLink] = $this->serviceTicketService->updateStatus(
                $service,
                $targetStatus,
                $request->input('catatan'),
                $pegawai
            );

            if ($targetStatus === StatusService::Selesai && empty($updatedService->tanggal_selesai)) {
                $updatedService->updateQuietly(['tanggal_selesai' => now()]);
            }

            $updatedService->load(['perangkat', 'pegawai', 'teknisi', 'parts', 'riwayat.pegawai']);

            return response()->json([
                'status'  => 'success',
                'message' => "Status servis diubah menjadi {$targetStatus->value}.",
                'data'    => [
                    'service' => $this->formatServiceResponse($updatedService),
                    'wa_link' => $waLink,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    public function storePart(Request $request, Service $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_part'  => ['required', 'string', 'max:255'],
            'jumlah'     => ['required', 'integer', 'min:1'],
            'harga'      => ['required', 'integer', 'min:0'],
            'produk_id'  => ['nullable', 'exists:produk,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data part tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $part = $this->serviceTicketService->tambahPart($service, $request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Part servis berhasil ditambahkan.',
                'data'    => $part,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    public function destroyPart(Service $service, PartService $part): JsonResponse
    {
        if ($part->service_id !== $service->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Part tidak terdaftar pada servis ini.',
            ], 404);
        }

        $this->serviceTicketService->hapusPart($service, $part);

        return response()->json([
            'status'  => 'success',
            'message' => 'Part servis berhasil dihapus.',
        ]);
    }

    public function perangkatIndex(Request $request): JsonResponse
    {
        $query = Perangkat::query()->withCount('services');

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari) {
                $q->where('nama_customer', 'like', "%{$cari}%")
                  ->orWhere('nomor_hp_customer', 'like', "%{$cari}%")
                  ->orWhere('merk_model', 'like', "%{$cari}%")
                  ->orWhere('kode_perangkat', 'like', "%{$cari}%")
                  ->orWhere('serial_number', 'like', "%{$cari}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $perangkat = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $perangkat->items(),
            'pagination' => [
                'current_page' => $perangkat->currentPage(),
                'last_page'    => $perangkat->lastPage(),
                'per_page'     => $perangkat->perPage(),
                'total'        => $perangkat->total(),
            ],
        ]);
    }

    public function perangkatStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_customer'     => ['required', 'string', 'max:255'],
            'nomor_hp_customer' => ['nullable', 'string', 'max:30'],
            'merk_model'        => ['required', 'string', 'max:255'],
            'serial_number'     => ['nullable', 'string', 'max:100'],
            'tahun'             => ['nullable', 'string', 'max:10'],
            'spesifikasi'       => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data perangkat tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kodePerangkat = 'DEV-' . strtoupper(\Illuminate\Support\Str::random(8));

        $perangkat = Perangkat::create([
            'kode_perangkat'    => $kodePerangkat,
            'nama_customer'     => (string) $request->input('nama_customer'),
            'nomor_hp_customer' => $request->input('nomor_hp_customer'),
            'merk_model'        => (string) $request->input('merk_model'),
            'serial_number'     => $request->input('serial_number'),
            'tahun'             => $request->input('tahun'),
            'spesifikasi'       => $request->input('spesifikasi'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Perangkat berhasil didaftarkan.',
            'data'    => $perangkat,
        ], 201);
    }

    public function perangkatShow(Perangkat $perangkat): JsonResponse
    {
        $perangkat->load(['services.parts', 'services.riwayat']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                => $perangkat->id,
                'kode_perangkat'    => $perangkat->kode_perangkat,
                'nama_customer'     => $perangkat->nama_customer,
                'nomor_hp_customer' => $perangkat->nomor_hp_customer,
                'merk_model'        => $perangkat->merk_model,
                'serial_number'     => $perangkat->serial_number,
                'tahun'             => $perangkat->tahun,
                'spesifikasi'       => $perangkat->spesifikasi,
                'services'          => $perangkat->services->map(fn (Service $s) => [
                    'id'               => $s->id,
                    'nomor_resi'       => $s->nomor_resi,
                    'status'           => $s->status->value,
                    'status_warna'     => $s->status->warna(),
                    'keluhan'          => $s->keluhan,
                    'catatan_solusi'   => $s->catatan_solusi,
                    'tanggal_masuk'    => $s->tanggal_masuk?->format('Y-m-d'),
                    'tanggal_selesai'  => $s->tanggal_selesai?->format('Y-m-d'),
                    'total_biaya'      => $s->totalBiaya(),
                ]),
            ],
        ]);
    }

    public function perangkatUpdate(Request $request, Perangkat $perangkat): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_customer'     => ['required', 'string', 'max:255'],
            'nomor_hp_customer' => ['nullable', 'string', 'max:30'],
            'merk_model'        => ['required', 'string', 'max:255'],
            'serial_number'     => ['nullable', 'string', 'max:100'],
            'tahun'             => ['nullable', 'string', 'max:10'],
            'spesifikasi'       => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data perangkat tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $perangkat->update($request->all());

        return response()->json([
            'status'  => 'success',
            'message' => 'Perangkat berhasil diperbarui.',
            'data'    => $perangkat,
        ]);
    }

    private function formatServiceResponse(Service $s): array
    {
        return [
            'id'                => $s->id,
            'nomor_resi'        => $s->nomor_resi,
            'status'            => $s->status->value,
            'status_warna'      => $s->status->warna(),
            'keluhan'           => $s->keluhan,
            'catatan_solusi'    => $s->catatan_solusi,
            'tanggal_masuk'     => $s->tanggal_masuk?->format('Y-m-d'),
            'estimasi_selesai'  => $s->estimasi_selesai?->format('Y-m-d'),
            'tanggal_selesai'   => $s->tanggal_selesai?->format('Y-m-d'),
            'biaya_service'     => (int) $s->biaya_service,
            'biaya_parts'       => (int) $s->parts->sum('subtotal'),
            'total_biaya'       => $s->totalBiaya(),
            'perangkat'         => $s->perangkat ? [
                'id'                => $s->perangkat->id,
                'kode_perangkat'    => $s->perangkat->kode_perangkat,
                'nama_customer'     => $s->perangkat->nama_customer,
                'nomor_hp_customer' => $s->perangkat->nomor_hp_customer,
                'merk_model'        => $s->perangkat->merk_model,
                'serial_number'     => $s->perangkat->serial_number,
                'tahun'             => $s->perangkat->tahun,
                'spesifikasi'       => $s->perangkat->spesifikasi,
            ] : null,
            'pegawai'           => $s->pegawai ? [
                'id'                => $s->pegawai->id,
                'nama_pegawai'      => $s->pegawai->nama_pegawai,
            ] : null,
            'teknisi'           => $s->teknisi ? [
                'id'                => $s->teknisi->id,
                'nama_pegawai'      => $s->teknisi->nama_pegawai,
            ] : null,
            'parts'             => $s->parts->map(fn ($p) => [
                'id'        => $p->id,
                'produk_id' => $p->produk_id,
                'nama_part' => $p->nama_part,
                'jumlah'    => (int) $p->jumlah,
                'harga'     => (int) $p->harga,
                'subtotal'  => (int) $p->subtotal,
            ]),
            'riwayat'           => $s->riwayat->map(fn ($r) => [
                'id'           => $r->id,
                'status'       => $r->status->value,
                'status_warna' => $r->status->warna(),
                'catatan'      => $r->catatan,
                'pegawai'      => $r->pegawai?->nama_pegawai,
                'waktu'        => $r->created_at?->format('Y-m-d H:i'),
            ]),
        ];
    }
}

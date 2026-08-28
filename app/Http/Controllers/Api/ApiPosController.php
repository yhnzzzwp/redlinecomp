<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\CartLine;
use App\Data\CheckoutData;
use App\Enums\MetodeBayar;
use App\Enums\StatusService;
use App\Enums\TipeItem;
use App\Enums\TransaksiStatus;
use App\Exceptions\PosException;
use App\Http\Controllers\Controller;
use App\Models\ItemTransaksi;
use App\Models\KategoriProduk;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Transaksi;
use App\Services\KodeGenerator;
use App\Services\PosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiPosController extends Controller
{
    public function __construct(
        private readonly PosService $posService,
        private readonly KodeGenerator $kodeGenerator,
    ) {}

    public function items(Request $request): JsonResponse
    {
        $produk = Produk::query()
            ->with('kategori')
            ->orderBy('nama_produk')
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'tipe'          => 'produk',
                'nama'          => $p->nama_produk,
                'sku'           => $p->sku,
                'kategori_id'   => $p->kategori_id,
                'nama_kategori' => $p->kategori?->nama_kategori,
                'harga'         => $p->harga ?? 0,
                'deskripsi'     => $p->deskripsi_produk,
            ]);

        $services = Service::query()
            ->with(['perangkat', 'parts'])
            ->where('status', '!=', StatusService::SudahDiambil)
            ->get()
            ->sortBy(fn (Service $s) => $s->status === StatusService::Selesai ? 0 : 1)
            ->values()
            ->map(fn (Service $s) => [
                'id'            => $s->id,
                'tipe'          => 'service',
                'nomor_resi'    => $s->nomor_resi,
                'nama'          => 'Servis: ' . ($s->perangkat?->merk_model ?? 'Unit #' . $s->nomor_resi),
                'nama_customer' => $s->perangkat?->nama_customer,
                'status'        => $s->status->value,
                'status_warna'  => $s->status->warna(),
                'siap_diambil'  => $s->status === StatusService::Selesai,
                'biaya_service' => (int) $s->biaya_service,
                'biaya_parts'   => (int) $s->parts->sum('subtotal'),
                'total_biaya'   => $s->totalBiaya(),
                'harga'         => $s->totalBiaya(),
            ]);

        $kategori = KategoriProduk::query()->orderBy('nama_kategori')->get();

        $now = now();
        $promos = Promo::query()
            ->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('waktu_mulai')->orWhere('waktu_mulai', '<=', $now))
            ->where(fn ($q) => $q->whereNull('waktu_berakhir')->orWhere('waktu_berakhir', '>=', $now))
            ->get();

        $metodeBayar = array_map(fn (MetodeBayar $m) => [
            'value' => $m->value,
            'label' => $m->name,
        ], MetodeBayar::cases());

        return response()->json([
            'status' => 'success',
            'data'   => [
                'produk'       => $produk,
                'services'     => $services,
                'kategori'     => $kategori,
                'promo'        => $promos,
                'metode_bayar' => $metodeBayar,
            ],
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.tipe'          => ['required', 'string', 'in:produk,service'],
            'items.*.produk_id'     => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.service_id'    => ['nullable', 'integer', 'exists:service,id'],
            'items.*.jumlah'        => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.harga'         => ['required', 'numeric', 'min:0'],
            'metode_bayar'          => ['required', Rule::enum(MetodeBayar::class)],
            'bayar'                 => ['required', 'integer', 'min:0'],
            'kode_promo'            => ['nullable', 'string', 'max:50'],
            'nama_pembeli'          => ['nullable', 'string', 'max:255'],
            'nomor_hp_pembeli'      => ['nullable', 'string', 'max:30'],
            'local_id'              => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data checkout tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $localId = $request->input('local_id');
        if (! empty($localId)) {
            $existing = Transaksi::where('local_id', $localId)->first();
            if ($existing) {
                $existing->load(['items', 'promo', 'pegawai']);
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Transaksi sudah pernah diproses.',
                    'data'    => $this->formatTransaksiPayload($existing),
                ]);
            }
        }

        /** @var Pegawai|null $kasir */
        $kasir = $request->user();
        if (! $kasir) {
            $kasir = Pegawai::first();
        }

        try {
            $cartLines = array_map(
                static fn (array $r): CartLine => new CartLine(
                    $r['tipe'],
                    (int) ($r['tipe'] === 'service' ? ($r['service_id'] ?? $r['itemId'] ?? 0) : ($r['produk_id'] ?? $r['itemId'] ?? 0)),
                    (int) $r['jumlah'],
                    (int) $r['harga']
                ),
                $request->input('items'),
            );

            $checkoutData = new CheckoutData(
                items: $cartLines,
                metodeBayar: MetodeBayar::from((string) $request->input('metode_bayar')),
                bayar: (int) $request->input('bayar'),
                kodePromo: $request->input('kode_promo'),
                namaPembeli: $request->input('nama_pembeli', 'Umum'),
                nomorHpPembeli: $request->input('nomor_hp_pembeli'),
            );

            $transaksi = $this->posService->checkout($checkoutData, $kasir);

            if (! empty($localId)) {
                $transaksi->updateQuietly(['local_id' => $localId]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => "Transaksi berhasil. Nota #{$transaksi->kode_nota}",
                'data'    => $this->formatTransaksiPayload($transaksi),
            ], 201);
        } catch (PosException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        $payload = $request->all();
        $transactions = isset($payload['transaksi']) && is_array($payload['transaksi'])
            ? $payload['transaksi']
            : [$payload];

        $synced = [];
        $errors = [];

        foreach ($transactions as $idx => $trxData) {
            $validator = Validator::make($trxData, [
                'local_id'         => ['required', 'string', 'max:255'],
                'pegawai_id'       => ['nullable', 'exists:pegawai,id'],
                'kode_promo'       => ['nullable', 'string', 'exists:promo,kode_promo'],
                'metode_bayar'     => ['required', 'string'],
                'nama_pembeli'     => ['nullable', 'string', 'max:255'],
                'nomor_hp_pembeli' => ['nullable', 'string', 'max:30'],
                'bayar'            => ['required', 'integer', 'min:0'],
                'items'            => ['required', 'array', 'min:1'],
                'items.*.tipe'     => ['required', 'string'],
                'items.*.produk_id' => ['nullable', 'exists:produk,id'],
                'items.*.service_id'=> ['nullable', 'exists:service,id'],
                'items.*.nama_item' => ['required', 'string', 'max:255'],
                'items.*.jumlah'    => ['required', 'integer', 'min:1'],
                'items.*.harga'     => ['required', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'index'    => $idx,
                    'local_id' => $trxData['local_id'] ?? null,
                    'errors'   => $validator->errors()->all(),
                ];
                continue;
            }

            $existing = Transaksi::where('local_id', $trxData['local_id'])->first();
            if ($existing) {
                $synced[] = [
                    'local_id'  => $existing->local_id,
                    'kode_nota' => $existing->kode_nota,
                    'status'    => 'already_synced',
                ];
                continue;
            }

            try {
                $record = DB::transaction(function () use ($trxData) {
                    $subtotal = 0;
                    foreach ($trxData['items'] as $item) {
                        $subtotal += ((int) $item['harga']) * ((int) $item['jumlah']);
                    }

                    $diskon = 0;
                    $promoId = null;
                    if (! empty($trxData['kode_promo'])) {
                        $promo = Promo::where('kode_promo', $trxData['kode_promo'])->first();
                        if ($promo && $promo->sedangBerlaku() && $subtotal >= (int) $promo->minimal_transaksi) {
                            $diskon = match ($promo->tipe_promo) {
                                \App\Enums\TipePromo::Persen => intdiv($subtotal * (int) $promo->besar_promo, 100),
                                \App\Enums\TipePromo::Nominal => (int) $promo->besar_promo,
                            };
                            if ($promo->maksimal_diskon !== null) {
                                $diskon = min($diskon, (int) $promo->maksimal_diskon);
                            }
                            $diskon = min($diskon, $subtotal);
                            $promoId = $promo->id;
                            $promo->increment('terpakai');
                        }
                    }

                    $total = max(0, $subtotal - $diskon);
                    $bayar = (int) $trxData['bayar'];
                    $kembalian = max(0, $bayar - $total);

                    $transaksi = Transaksi::create([
                        'kode_nota'        => (new KodeGenerator())->nota(),
                        'local_id'         => $trxData['local_id'],
                        'pegawai_id'       => $trxData['pegawai_id'] ?? null,
                        'promo_id'         => $promoId,
                        'metode_bayar'     => MetodeBayar::tryFrom($trxData['metode_bayar']) ?? MetodeBayar::Tunai,
                        'subtotal'         => $subtotal,
                        'diskon'           => $diskon,
                        'total'            => $total,
                        'bayar'            => $bayar,
                        'kembalian'        => $kembalian,
                        'nama_pembeli'     => $trxData['nama_pembeli'] ?? 'Umum',
                        'nomor_hp_pembeli' => $trxData['nomor_hp_pembeli'] ?? null,
                        'status'           => TransaksiStatus::Normal,
                    ]);

                    foreach ($trxData['items'] as $item) {
                        $isService = strtolower($item['tipe']) === 'service' || strtolower($item['tipe']) === 'servis';
                        ItemTransaksi::create([
                            'transaksi_id' => $transaksi->id,
                            'tipe'         => $isService ? TipeItem::Servis : TipeItem::Produk,
                            'produk_id'    => ! $isService ? ($item['produk_id'] ?? null) : null,
                            'service_id'   => $isService ? ($item['service_id'] ?? null) : null,
                            'nama_item'    => $item['nama_item'],
                            'jumlah'       => (int) $item['jumlah'],
                            'harga'        => (int) $item['harga'],
                            'subtotal'     => ((int) $item['harga']) * ((int) $item['jumlah']),
                        ]);

                        if ($isService && ! empty($item['service_id'])) {
                            $srv = Service::find($item['service_id']);
                            if ($srv && $srv->status !== StatusService::SudahDiambil) {
                                $srv->update(['status' => StatusService::SudahDiambil]);
                                $srv->riwayat()->create([
                                    'pegawai_id' => $transaksi->pegawai_id,
                                    'status'     => StatusService::SudahDiambil,
                                    'catatan'    => 'Dibayar & diambil via POS — Nota #' . $transaksi->kode_nota,
                                ]);
                            }
                        }
                    }

                    return $transaksi;
                });

                $synced[] = [
                    'local_id'  => $record->local_id,
                    'kode_nota' => $record->kode_nota,
                    'status'    => 'synced',
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'index'    => $idx,
                    'local_id' => $trxData['local_id'],
                    'errors'   => [$e->getMessage()],
                ];
            }
        }

        return response()->json([
            'status' => count($errors) === 0 ? 'success' : 'partial',
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }

    public function nota(Transaksi $transaksi): JsonResponse
    {
        $transaksi->load(['items', 'promo', 'pegawai']);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatTransaksiPayload($transaksi),
        ]);
    }

    public function struk(Transaksi $transaksi): JsonResponse
    {
        $transaksi->load(['items', 'promo', 'pegawai']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'transaksi'   => $this->formatTransaksiPayload($transaksi),
                'toko'        => [
                    'nama'    => config('redline.store.name', 'Redline Komputer'),
                    'alamat'  => config('redline.store.address', 'Jl. Sukarno Hatta, Salatiga'),
                    'telepon' => config('redline.store.phone', '085640203069'),
                    'wa'      => config('redline.wa.number', '6285640203069'),
                ],
            ],
        ]);
    }

    private function formatTransaksiPayload(Transaksi $t): array
    {
        return [
            'id'               => $t->id,
            'kode_nota'        => $t->kode_nota,
            'local_id'         => $t->local_id,
            'status'           => $t->status->value ?? (string) $t->status,
            'metode_bayar'     => $t->metode_bayar->value ?? (string) $t->metode_bayar,
            'subtotal'         => (int) $t->subtotal,
            'diskon'           => (int) $t->diskon,
            'total'            => (int) $t->total,
            'bayar'            => (int) $t->bayar,
            'kembalian'        => (int) $t->kembalian,
            'nama_pembeli'     => $t->nama_pembeli,
            'nomor_hp_pembeli' => $t->nomor_hp_pembeli,
            'created_at'       => $t->created_at?->format('Y-m-d H:i:s'),
            'kasir'            => $t->pegawai ? [
                'id'           => $t->pegawai->id,
                'nama'         => $t->pegawai->nama_pegawai,
            ] : null,
            'promo'            => $t->promo ? [
                'id'           => $t->promo->id,
                'kode_promo'   => $t->promo->kode_promo,
                'nama_promo'   => $t->promo->nama_promo,
            ] : null,
            'items'            => $t->items->map(fn ($item) => [
                'id'           => $item->id,
                'tipe'         => $item->tipe->value ?? (string) $item->tipe,
                'produk_id'    => $item->produk_id,
                'service_id'   => $item->service_id,
                'nama_item'    => $item->nama_item,
                'jumlah'       => (int) $item->jumlah,
                'harga'        => (int) $item->harga,
                'subtotal'     => (int) $item->subtotal,
            ]),
        ];
    }
}

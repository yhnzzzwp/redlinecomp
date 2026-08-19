<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\CartLine;
use App\Data\CheckoutData;
use App\Enums\MetodeBayar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.tipe' => ['required', 'string', 'in:produk,service'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.service_id' => ['nullable', 'integer', 'exists:service,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.harga' => ['required', 'numeric', 'min:0'],
            'metode_bayar' => ['required', Rule::enum(MetodeBayar::class)],
            'bayar' => ['required', 'integer', 'min:0'],
            'kode_promo' => ['nullable', 'string', 'max:50'],
            'nama_pembeli' => ['nullable', 'string', 'max:255'],
            'nomor_hp_pembeli' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function toCheckoutData(): CheckoutData
    {
        $items = array_map(
            static fn (array $r): CartLine => new CartLine(
                $r['tipe'],
                (int) ($r['tipe'] === 'service' ? $r['service_id'] : $r['produk_id']),
                (int) $r['jumlah'],
                (int) $r['harga']
            ),
            $this->validated('items'),
        );

        return new CheckoutData(
            items: $items,
            metodeBayar: MetodeBayar::from($this->validated('metode_bayar')),
            bayar: (int) $this->validated('bayar'),
            kodePromo: $this->validated('kode_promo'),
            namaPembeli: $this->validated('nama_pembeli'),
            nomorHpPembeli: $this->validated('nomor_hp_pembeli'),
        );
    }
}

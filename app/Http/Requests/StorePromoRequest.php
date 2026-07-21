<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TipePromo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_promo' => strtoupper((string) $this->input('kode_promo')),
            'aktif' => $this->boolean('aktif'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_promo' => ['required', 'string', 'max:255'],
            'kode_promo' => ['required', 'string', 'max:50', 'alpha_num', Rule::unique('promo', 'kode_promo')->ignore($this->route('promo'))],
            'tipe_promo' => ['required', Rule::enum(TipePromo::class)],
            'besar_promo' => ['required', 'integer', 'min:1', $this->input('tipe_promo') === TipePromo::Persen->value ? 'max:100' : 'max:100000000000'],
            'minimal_transaksi' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'maksimal_diskon' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'kuota' => ['nullable', 'integer', 'min:1'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_berakhir' => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_promo.unique' => 'Kode promo sudah dipakai.',
            'kode_promo.alpha_num' => 'Kode promo hanya boleh huruf dan angka.',
            'besar_promo.max' => 'Untuk tipe Persen, besaran maksimal 100.',
            'waktu_berakhir.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
    }
}

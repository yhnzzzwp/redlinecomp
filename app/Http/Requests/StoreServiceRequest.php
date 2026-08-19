<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'perangkat_id' => ['required', 'exists:perangkat,id'],
            'keluhan' => ['required', 'string', 'max:5000'],
            'biaya_service' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'estimasi_selesai' => ['nullable', 'date', 'after_or_equal:today'],
            'teknisi_id' => ['nullable', 'integer', 'exists:pegawai,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'perangkat_id.required' => 'Perangkat wajib dipilih.',
            'perangkat_id.exists' => 'Perangkat tidak ditemukan.',
            'keluhan.required' => 'Keluhan wajib diisi.',
        ];
    }
}

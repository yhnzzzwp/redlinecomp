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
            'nama_customer' => ['required', 'string', 'max:255'],
            'nomor_hp_customer' => ['nullable', 'string', 'max:30'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'masalah' => ['required', 'string', 'max:5000'],
            'biaya_service' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'estimasi_selesai' => ['nullable', 'date', 'after_or_equal:today'],
            'teknisi_id' => ['nullable', 'integer', 'exists:pegawai,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_customer.required' => 'Nama pelanggan wajib diisi.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'masalah.required' => 'Deskripsi masalah wajib diisi.',
        ];
    }
}

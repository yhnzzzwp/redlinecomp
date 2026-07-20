<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['show_katalog' => $this->boolean('show_katalog')]);
    }

    public function rules(): array
    {
        return [
            'nama_produk' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('produk', 'sku')->ignore($this->route('produk'))],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori_produk,id'],
            'harga' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'jumlah_produk' => ['required', 'integer', 'min:0', 'max:1000000'],
            'deskripsi_produk' => ['nullable', 'string', 'max:5000'],
            'show_katalog' => ['boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'harga.required' => 'Harga wajib diisi.',
            'jumlah_produk.required' => 'Stok wajib diisi.',
            'sku.unique' => 'SKU sudah dipakai produk lain.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            'foto.mimes' => 'Foto harus JPG, PNG, atau WEBP.',
        ];
    }
}

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
            'deskripsi_produk' => ['nullable', 'string', 'max:5000'],
            'show_katalog' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'sku.unique' => 'SKU sudah dipakai produk lain.',
        ];
    }
}

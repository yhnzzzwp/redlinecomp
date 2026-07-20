<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nama_part' => ['required', 'string', 'max:255'],
            'produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'harga' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }
}

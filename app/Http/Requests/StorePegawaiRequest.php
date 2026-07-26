<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RolePegawai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StorePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_pegawai' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('pegawai', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('pegawai', 'email')],
            'role' => ['required', Rule::enum(RolePegawai::class)],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'alamat_pegawai' => ['nullable', 'string', 'max:500'],
            'tanggal_masuk' => ['nullable', 'date'],
            'masih_bekerja' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'masih_bekerja' => $this->boolean('masih_bekerja'),
        ]);
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username sudah dipakai pegawai lain.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}

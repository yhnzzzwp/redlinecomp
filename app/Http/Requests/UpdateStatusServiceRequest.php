<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StatusService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusService::class)],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

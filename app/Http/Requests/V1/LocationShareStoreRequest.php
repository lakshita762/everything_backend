<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class LocationShareStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'allow_live_tracking' => ['nullable', 'boolean'],
            'allow_history' => ['nullable', 'boolean'],
            'expires_in' => ['nullable', 'integer', 'min:5', 'max:10080'],
        ];
    }
}
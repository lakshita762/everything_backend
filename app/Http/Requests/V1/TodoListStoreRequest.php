<?php

namespace App\Http\Requests\V1;

use App\Enums\TodoListVisibility;
use Illuminate\Foundation\Http\FormRequest;

class TodoListStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'visibility' => ['nullable', 'in:' . implode(',', array_map(fn ($v) => $v->value, TodoListVisibility::cases()))],
        ];
    }
}
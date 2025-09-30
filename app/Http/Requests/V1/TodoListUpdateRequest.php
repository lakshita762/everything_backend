<?php

namespace App\Http\Requests\V1;

use App\Enums\TodoListVisibility;
use Illuminate\Foundation\Http\FormRequest;

class TodoListUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'visibility' => ['sometimes', 'in:' . implode(',', array_map(fn ($v) => $v->value, TodoListVisibility::cases()))],
        ];
    }
}
<?php

namespace App\Http\Requests\V1;

use App\Enums\TodoListRole;
use Illuminate\Foundation\Http\FormRequest;

class TodoListShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roles = collect(TodoListRole::cases())
            ->reject(fn (TodoListRole $role) => $role === TodoListRole::OWNER)
            ->map(fn (TodoListRole $role) => $role->value)
            ->implode(',');

        return [
            'email' => ['required', 'email'],
            'role' => ['required', 'in:' . $roles],
        ];
    }
}
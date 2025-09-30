<?php

namespace App\Http\Requests\V1;

use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use Illuminate\Foundation\Http\FormRequest;

class TodoListShareUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roles = implode(',', array_map(fn ($role) => $role->value, TodoListRole::cases()));
        $statuses = implode(',', array_map(fn ($status) => $status->value, TodoListMembershipStatus::cases()));

        return [
            'role' => ['sometimes', 'in:' . $roles],
            'status' => ['sometimes', 'in:' . $statuses],
        ];
    }
}
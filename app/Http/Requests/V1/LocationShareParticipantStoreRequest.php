<?php

namespace App\Http\Requests\V1;

use App\Enums\LocationParticipantRole;
use Illuminate\Foundation\Http\FormRequest;

class LocationShareParticipantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roles = implode(',', array_map(fn ($role) => $role->value, LocationParticipantRole::cases()));

        return [
            'email' => ['required', 'email'],
            'role' => ['required', 'in:' . $roles],
        ];
    }
}
<?php

namespace App\Http\Requests\V1;

use App\Enums\LocationParticipantRole;
use App\Enums\LocationParticipantStatus;
use Illuminate\Foundation\Http\FormRequest;

class LocationShareParticipantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roles = implode(',', array_map(fn ($role) => $role->value, LocationParticipantRole::cases()));
        $statuses = implode(',', array_map(fn ($status) => $status->value, LocationParticipantStatus::cases()));

        return [
            'role' => ['sometimes', 'in:' . $roles],
            'status' => ['sometimes', 'in:' . $statuses],
        ];
    }
}
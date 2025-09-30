<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationParticipantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationShareParticipantStoreRequest;
use App\Http\Requests\V1\LocationShareParticipantUpdateRequest;
use App\Http\Resources\LocationShareParticipantResource;
use App\Models\LocationShare;
use App\Models\LocationShareParticipant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LocationShareParticipantController extends Controller
{
    public function store(LocationShareParticipantStoreRequest $request, LocationShare $share): JsonResponse
    {
        $this->authorize('manageParticipants', $share);
        $data = $request->validated();

        if (strcasecmp($data['email'], $share->owner->email ?? '') === 0) {
            throw ValidationException::withMessages([
                'email' => 'Owner already has access to this share.',
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        $participant = LocationShareParticipant::query()
            ->where('location_share_id', $share->id)
            ->where(function ($query) use ($user, $data) {
                if ($user) {
                    $query->where('user_id', $user->id);
                }
                $query->orWhere('email', $data['email']);
            })
            ->first();

        if (!$participant) {
            $participant = new LocationShareParticipant([
                'location_share_id' => $share->id,
            ]);
        }

        $participant->fill([
            'user_id' => $user?->id,
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => LocationParticipantStatus::PENDING->value,
            'invited_at' => now(),
            'responded_at' => null,
        ]);
        $participant->save();

        Log::info('location_share.invited', [
            'share_id' => $share->id,
            'owner_id' => $share->owner_id,
            'participant_id' => $participant->id,
            'email' => $participant->email,
        ]);

        return response()->json([
            'data' => [
                'participant' => new LocationShareParticipantResource($participant->fresh('user')),
            ],
        ], 201);
    }

    public function update(LocationShareParticipantUpdateRequest $request, LocationShare $share, LocationShareParticipant $participant): JsonResponse
    {
        $this->authorize('manageParticipants', $share);
        $this->ensureParticipantBelongsToShare($participant, $share);

        $data = $request->validated();

        if (array_key_exists('role', $data)) {
            $participant->role = $data['role'];
        }

        if (array_key_exists('status', $data)) {
            if ($data['status'] === LocationParticipantStatus::ACCEPTED->value || $data['status'] === LocationParticipantStatus::REVOKED->value) {
                $participant->responded_at = now();
            }
            $participant->status = $data['status'];
        }

        $participant->save();
        $participant->load('user');

        Log::info('location_share.participant_updated', [
            'share_id' => $share->id,
            'participant_id' => $participant->id,
        ]);

        return response()->json([
            'data' => [
                'participant' => new LocationShareParticipantResource($participant),
            ],
        ]);
    }

    public function destroy(LocationShare $share, LocationShareParticipant $participant): JsonResponse
    {
        $this->authorize('manageParticipants', $share);
        $this->ensureParticipantBelongsToShare($participant, $share);

        $participant->delete();

        Log::info('location_share.participant_removed', [
            'share_id' => $share->id,
            'participant_id' => $participant->id,
        ]);

        return response()->json([
            'data' => [
                'message' => 'Participant removed',
            ],
        ]);
    }

    private function ensureParticipantBelongsToShare(LocationShareParticipant $participant, LocationShare $share): void
    {
        if ($participant->location_share_id !== $share->id) {
            throw ValidationException::withMessages([
                'participant' => 'Participant does not belong to this share.',
            ]);
        }
    }
}
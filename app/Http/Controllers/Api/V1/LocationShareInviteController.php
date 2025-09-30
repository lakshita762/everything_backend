<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationParticipantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationShareParticipantResource;
use App\Models\LocationShareParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LocationShareInviteController extends Controller
{
    public function accept(Request $request, LocationShareParticipant $participant): JsonResponse
    {
        $user = $request->user();
        $this->guardInvite($participant, $user->id, $user->email);

        DB::transaction(function () use ($participant, $user) {
            $participant->user_id = $user->id;
            $participant->status = LocationParticipantStatus::ACCEPTED->value;
            $participant->responded_at = now();
            $participant->save();
        });

        Log::info('location_share.invite_accepted', [
            'participant_id' => $participant->id,
            'share_id' => $participant->location_share_id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'data' => [
                'participant' => new LocationShareParticipantResource($participant->fresh(['share.owner', 'user'])),
            ],
        ]);
    }

    public function decline(Request $request, LocationShareParticipant $participant): JsonResponse
    {
        $user = $request->user();
        $this->guardInvite($participant, $user->id, $user->email);

        DB::transaction(function () use ($participant, $user) {
            $participant->status = LocationParticipantStatus::REVOKED->value;
            $participant->responded_at = now();
            if (!$participant->user_id) {
                $participant->user_id = $user->id;
            }
            $participant->save();
        });

        Log::info('location_share.invite_declined', [
            'participant_id' => $participant->id,
            'share_id' => $participant->location_share_id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'data' => [
                'participant' => new LocationShareParticipantResource($participant->fresh(['share.owner', 'user'])),
            ],
        ]);
    }

    private function guardInvite(LocationShareParticipant $participant, int $userId, string $email): void
    {
        if ($participant->status?->value !== LocationParticipantStatus::PENDING->value) {
            throw ValidationException::withMessages([
                'invite' => 'Invite is no longer active.',
            ]);
        }

        if ($participant->user_id && $participant->user_id !== $userId) {
            throw ValidationException::withMessages([
                'invite' => 'Invite does not belong to this user.',
            ]);
        }

        if ($participant->email && !hash_equals(strtolower($participant->email), strtolower($email))) {
            throw ValidationException::withMessages([
                'invite' => 'Invite does not belong to this user.',
            ]);
        }
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationParticipantStatus;
use App\Enums\LocationShareStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationShareStoreRequest;
use App\Http\Resources\LocationShareResource;
use App\Http\Resources\LocationShareParticipantResource;
use App\Models\LocationShare;
use App\Models\LocationShareParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationShareController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $includes = collect(explode(',', (string) $request->query('include', '')))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->values();

        $with = ['owner', 'latestPoint'];
        $includeParticipants = $includes->contains('participants');

        if ($includeParticipants) {
            $with[] = 'participants.user';
        }

        $outgoing = LocationShare::query()
            ->where('owner_id', $user->id)
            ->with($with)
            ->orderByDesc('updated_at')
            ->get();

        $incoming = LocationShare::query()
            ->where('owner_id', '!=', $user->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', LocationParticipantStatus::ACCEPTED->value);
            })
            ->with($with)
            ->orderByDesc('updated_at')
            ->get();

        if ($includeParticipants) {
            $incoming->each(function (LocationShare $share) use ($user) {
                $share->setRelation(
                    'participants',
                    $share->participants->filter(fn ($participant) => $participant->status->value === LocationParticipantStatus::ACCEPTED->value)
                );
            });
        }

        $invites = LocationShareParticipant::query()
            ->where('status', LocationParticipantStatus::PENDING->value)
            ->where(function ($query) use ($user) {
                $query->where('email', $user->email)
                    ->orWhere('user_id', $user->id);
            })
            ->with(['share.owner'])
            ->orderByDesc('invited_at')
            ->get();

        return response()->json([
            'data' => [
                'outgoing' => LocationShareResource::collection($outgoing),
                'incoming' => LocationShareResource::collection($incoming),
                'invites' => LocationShareParticipantResource::collection($invites),
            ],
        ]);
    }

    public function store(LocationShareStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $share = LocationShare::create([
            'owner_id' => $user->id,
            'name' => $payload['name'] ?? null,
            'allow_live_tracking' => $payload['allow_live_tracking'] ?? true,
            'allow_history' => $payload['allow_history'] ?? true,
            'status' => LocationShareStatus::ACTIVE->value,
            'expires_at' => $payload['expires_in'] ?? null
                ? now()->addMinutes((int) $payload['expires_in'])
                : null,
        ]);

        $share->load(['owner', 'latestPoint']);

        Log::info('location_share.created', [
            'share_id' => $share->id,
            'owner_id' => $user->id,
        ]);

        return response()->json([
            'data' => [
                'share' => new LocationShareResource($share),
            ],
        ], 201);
    }

    public function stop(Request $request, LocationShare $share): JsonResponse
    {
        $this->authorize('stop', $share);

        $share->status = LocationShareStatus::STOPPED->value;
        $share->save();

        Log::info('location_share.stopped', [
            'share_id' => $share->id,
            'owner_id' => $share->owner_id,
        ]);

        return response()->json([
            'data' => [
                'share' => new LocationShareResource($share->fresh(['owner', 'latestPoint'])),
            ],
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TodoListInviteResource;
use App\Models\TodoListInvite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TodoListInviteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $invites = TodoListInvite::query()
            ->where('email', $user->email)
            ->where('status', TodoListInviteStatus::PENDING->value)
            ->with(['list.owner'])
            ->orderBy('invited_at', 'desc')
            ->get();

        return response()->json([
            'data' => [
                'invites' => TodoListInviteResource::collection($invites),
            ],
        ]);
    }

    public function accept(Request $request, TodoListInvite $invite): JsonResponse
    {
        $user = $request->user();
        $this->guardInviteForUser($invite, $user);

        DB::transaction(function () use ($invite, $user) {
            $invite->status = TodoListInviteStatus::ACCEPTED;
            $invite->save();

            $list = $invite->list;

            $list->memberships()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role' => $invite->role->value,
                    'status' => TodoListMembershipStatus::ACCEPTED->value,
                    'invited_at' => $invite->invited_at ?? now(),
                ]
            );
        });

        return response()->json([
            'data' => [
                'invite' => new TodoListInviteResource($invite->fresh(['list.owner'])),
            ],
        ]);
    }

    public function decline(Request $request, TodoListInvite $invite): JsonResponse
    {
        $user = $request->user();
        $this->guardInviteForUser($invite, $user);

        DB::transaction(function () use ($invite, $user) {
            $invite->status = TodoListInviteStatus::DECLINED;
            $invite->save();

            $membership = $invite->list->memberships()
                ->where('user_id', $user->id)
                ->where('status', TodoListMembershipStatus::PENDING->value)
                ->first();

            if ($membership) {
                $membership->delete();
            }
        });

        return response()->json([
            'data' => [
                'invite' => new TodoListInviteResource($invite->fresh(['list.owner'])),
            ],
        ]);
    }

    private function guardInviteForUser(TodoListInvite $invite, User $user): void
    {
        if ($invite->status?->value !== TodoListInviteStatus::PENDING->value) {
            throw ValidationException::withMessages([
                'invite' => 'Invite is no longer active.',
            ]);
        }

        if (!hash_equals(strtolower($invite->email), strtolower($user->email))) {
            throw ValidationException::withMessages([
                'invite' => 'Invite does not belong to this user.',
            ]);
        }

        if ($invite->expires_at && now()->greaterThan($invite->expires_at)) {
            $invite->status = TodoListInviteStatus::EXPIRED;
            $invite->save();

            throw ValidationException::withMessages([
                'invite' => 'Invite has expired.',
            ]);
        }
    }
}
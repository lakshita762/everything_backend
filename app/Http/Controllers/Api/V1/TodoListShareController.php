<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TodoListShareRequest;
use App\Http\Requests\V1\TodoListShareUpdateRequest;
use App\Http\Resources\TodoListCollaboratorResource;
use App\Http\Resources\TodoListInviteResource;
use App\Models\TodoList;
use App\Models\TodoListInvite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TodoListShareController extends Controller
{
    public function store(TodoListShareRequest $request, TodoList $todoList): JsonResponse
    {
        $this->ensureOwner($request->user(), $todoList);

        $data = $request->validated();

        if (strcasecmp($data['email'], $todoList->owner->email ?? '') === 0) {
            throw ValidationException::withMessages([
                'email' => 'Owner cannot be invited.',
            ]);
        }

        $role = $data['role'];

        $invite = DB::transaction(function () use ($todoList, $data, $role) {
            $existing = $todoList->invites()
                ->where('email', $data['email'])
                ->where('status', TodoListInviteStatus::PENDING->value)
                ->first();

            if ($existing) {
                $existing->fill([
                    'role' => $role,
                    'token' => Str::uuid()->toString(),
                    'expires_at' => now()->addDays(7),
                    'invited_at' => now(),
                ])->save();

                $invite = $existing;
            } else {
                $invite = $todoList->invites()->create([
                    'email' => $data['email'],
                    'role' => $role,
                    'token' => Str::uuid()->toString(),
                    'expires_at' => now()->addDays(7),
                    'status' => TodoListInviteStatus::PENDING->value,
                    'invited_at' => now(),
                ]);
            }

            $user = User::where('email', $data['email'])->first();
            if ($user) {
                $todoList->memberships()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'role' => $role,
                        'status' => TodoListMembershipStatus::PENDING->value,
                        'invited_at' => now(),
                    ]
                );
            }

            return $invite->load('list.owner');
        });

        return response()->json([
            'data' => [
                'invite' => new TodoListInviteResource($invite),
            ],
        ], 201);
    }

    public function update(TodoListShareUpdateRequest $request, TodoList $todoList, User $user): JsonResponse
    {
        $this->ensureOwner($request->user(), $todoList);

        if ($user->id === $todoList->owner_id) {
            throw ValidationException::withMessages([
                'user' => 'Owner membership cannot be modified.',
            ]);
        }

        $membership = $todoList->memberships()->where('user_id', $user->id)->firstOrFail();
        $data = $request->validated();

        if (array_key_exists('role', $data)) {
            if ($data['role'] === TodoListRole::OWNER->value) {
                throw ValidationException::withMessages([
                    'role' => 'Cannot assign owner role through this endpoint.',
                ]);
            }
            $membership->role = $data['role'];
        }

        if (array_key_exists('status', $data)) {
            $membership->status = $data['status'];
        }

        $membership->save();
        $membership->load('user');

        return response()->json([
            'data' => [
                'collaborator' => new TodoListCollaboratorResource($membership),
            ],
        ]);
    }

    public function destroy(Request $request, TodoList $todoList, User $user): JsonResponse
    {
        $this->ensureOwner($request->user(), $todoList);

        if ($user->id === $todoList->owner_id) {
            throw ValidationException::withMessages([
                'user' => 'Owner cannot be removed from the list.',
            ]);
        }

        $membership = $todoList->memberships()->where('user_id', $user->id)->first();
        if ($membership) {
            $membership->delete();
        }

        $todoList->invites()
            ->where('email', $user->email)
            ->where('status', TodoListInviteStatus::PENDING->value)
            ->delete();

        return response()->json([
            'data' => [
                'message' => 'Collaborator removed',
            ],
        ]);
    }

    private function ensureOwner(?User $user, TodoList $todoList): void
    {
        abort_unless($user && $user->id === $todoList->owner_id, 403);
    }
}
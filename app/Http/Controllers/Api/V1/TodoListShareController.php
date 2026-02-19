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
use App\Models\User;
use App\Services\Smtp2GoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $email = strtolower($data['email']);

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user) {
                $acceptedMembership = $todoList->memberships()
                    ->where('user_id', $user->id)
                    ->where('status', TodoListMembershipStatus::ACCEPTED->value)
                    ->exists();

                if ($acceptedMembership) {
                    throw ValidationException::withMessages([
                        'email' => 'This email is already a collaborator on this list.',
                    ]);
                }
            }

            $existingInvite = $todoList->invites()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existingInvite) {
                $existingInvite->fill([
                    'email' => $data['email'],
                    'role' => $role,
                    'token' => Str::uuid()->toString(),
                    'expires_at' => now()->addDays(7),
                    'status' => TodoListInviteStatus::PENDING->value,
                    'invited_at' => now(),
                ])->save();

                $invite = $existingInvite;
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

        // send invite email (best-effort; do not block response on failures)
        try {
            $list = $invite->list;
            $owner = $list?->owner;

            $subject = sprintf(
                '%s invited you to collaborate on "%s"',
                $owner?->name ?? 'A user',
                $list?->name ?? 'a todo list'
            );

            $html = view('emails.todo_list_invite', [
                'invite' => $invite,
                'list' => $list,
                'owner' => $owner,
            ])->render();

            app(Smtp2GoService::class)->send(
                to: $data['email'],
                subject: $subject,
                htmlBody: $html
            );
        } catch (\Throwable $e) {
            Log::warning('todo_list.invite_email_failed', [
                'todo_list_id' => $todoList->id,
                'invite_id' => $invite->id,
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);
        }

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

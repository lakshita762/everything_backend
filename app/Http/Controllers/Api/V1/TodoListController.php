<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use App\Enums\TodoListVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TodoListStoreRequest;
use App\Http\Requests\V1\TodoListUpdateRequest;
use App\Http\Resources\TodoListInviteResource;
use App\Http\Resources\TodoListResource;
use App\Models\TodoList;
use App\Models\TodoListInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TodoListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $includes = collect(explode(',', (string) $request->query('include', '')))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->values();

        $with = ['owner'];

        if ($includes->contains('tasks')) {
            $with[] = 'tasks.assignee';
        }

        if ($includes->contains('collaborators')) {
            $with[] = 'memberships.user';
        }

        if ($includes->contains('pending_invites')) {
            $with[] = 'invites';
        }

        $lists = TodoList::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', TodoListMembershipStatus::ACCEPTED->value);
            })
            ->with($with)
            ->orderByDesc('updated_at')
            ->get();

        $lists->each(function (TodoList $todoList) use ($user, $includes) {
            if (!$todoList->relationLoaded('memberships')) {
                $todoList->setRelation('memberships', collect());
            }

            if ($todoList->owner_id !== $user->id || !$includes->contains('pending_invites')) {
                $todoList->setRelation('invites', collect());
            }
        });

        $pendingInvites = TodoListInvite::query()
            ->where('email', $user->email)
            ->where('status', TodoListInviteStatus::PENDING->value)
            ->with(['list.owner'])
            ->get();

        return response()->json([
            'data' => [
                'lists' => TodoListResource::collection($lists),
                'invites' => TodoListInviteResource::collection($pendingInvites),
            ],
        ]);
    }

    public function store(TodoListStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $todoList = TodoList::create([
            'owner_id' => $user->id,
            'name' => $payload['name'],
            'visibility' => $payload['visibility'] ?? TodoListVisibility::PRIVATE->value,
        ]);

        $todoList->memberships()->create([
            'user_id' => $user->id,
            'role' => TodoListRole::OWNER->value,
            'status' => TodoListMembershipStatus::ACCEPTED->value,
            'invited_at' => now(),
        ]);

        $todoList->load(['owner', 'memberships.user', 'tasks', 'invites']);

        return response()->json([
            'data' => [
                'list' => new TodoListResource($todoList),
            ],
        ], 201);
    }

    public function update(TodoListUpdateRequest $request, TodoList $todoList): JsonResponse
    {
        abort_unless($todoList->canUserManage($request->user()), 403);

        $payload = $request->validated();

        $todoList->fill(Arr::only($payload, ['name', 'visibility']));
        $todoList->save();

        $todoList->refresh()->load(['owner', 'memberships.user', 'tasks', 'invites']);

        return response()->json([
            'data' => [
                'list' => new TodoListResource($todoList),
            ],
        ]);
    }

    public function destroy(Request $request, TodoList $todoList): JsonResponse
    {
        abort_unless($request->user()->id === $todoList->owner_id, 403);

        $todoList->delete();

        return response()->json([
            'data' => [
                'message' => 'List deleted',
            ],
        ]);
    }
}
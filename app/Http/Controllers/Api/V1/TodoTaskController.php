<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TodoListMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TodoTaskStoreRequest;
use App\Http\Requests\V1\TodoTaskUpdateRequest;
use App\Http\Resources\TodoTaskResource;
use App\Models\TodoList;
use App\Models\TodoTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TodoTaskController extends Controller
{
    public function store(TodoTaskStoreRequest $request, TodoList $todoList): JsonResponse
    {
        abort_unless($todoList->canUserManage($request->user()), 403);

        $data = $request->validated();
        $this->guardAssignee($todoList, $data);

        $task = $todoList->tasks()->create($data);
        $task->load('assignee');

        return response()->json([
            'data' => [
                'task' => new TodoTaskResource($task),
            ],
        ], 201);
    }

    public function update(TodoTaskUpdateRequest $request, TodoList $todoList, TodoTask $task): JsonResponse
    {
        abort_unless($todoList->canUserManage($request->user()), 403);
        $this->ensureTaskBelongsToList($task, $todoList);

        $data = $request->validated();
        $this->guardAssignee($todoList, $data);

        $task->fill($data);
        $task->save();
        $task->refresh()->load('assignee');

        return response()->json([
            'data' => [
                'task' => new TodoTaskResource($task),
            ],
        ]);
    }

    public function destroy(Request $request, TodoList $todoList, TodoTask $task): JsonResponse
    {
        abort_unless($todoList->canUserManage($request->user()), 403);
        $this->ensureTaskBelongsToList($task, $todoList);

        $task->delete();

        return response()->json([
            'data' => [
                'message' => 'Task deleted',
            ],
        ]);
    }

    private function ensureTaskBelongsToList(TodoTask $task, TodoList $todoList): void
    {
        if ($task->todo_list_id !== $todoList->id) {
            abort(404, 'Task does not belong to this list');
        }
    }

    private function guardAssignee(TodoList $todoList, array &$data): void
    {
        if (!array_key_exists('assigned_to', $data) || !$data['assigned_to']) {
            return;
        }

        $assignedId = (int) $data['assigned_to'];

        if ($assignedId === $todoList->owner_id) {
            return;
        }

        $isCollaborator = $todoList->memberships()
            ->where('user_id', $assignedId)
            ->where('status', TodoListMembershipStatus::ACCEPTED->value)
            ->exists();

        if (!$isCollaborator) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Assignee must be an accepted collaborator on this list.',
            ]);
        }
    }
}
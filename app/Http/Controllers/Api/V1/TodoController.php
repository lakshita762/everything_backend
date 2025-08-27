<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\TodoStoreRequest;
use App\Http\Requests\V1\TodoUpdateRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $todos = Todo::where('user_id',$request->user()->id)->latest()->paginate(20);
        return $this->success($todos);
    }

    public function store(TodoStoreRequest $request)
    {
        $todo = $request->user()->todos()->create($request->validated());
        return $this->success($todo, 'Created', 201);
    }

    public function show(Request $request, Todo $todo)
    {
        $this->authorizeOwner($request, $todo->user_id);
        return $this->success($todo);
    }

    public function update(TodoUpdateRequest $request, Todo $todo)
    {
        $this->authorizeOwner($request, $todo->user_id);
        $todo->update($request->validated());
        return $this->success($todo, 'Updated');
    }

    public function destroy(Request $request, Todo $todo)
    {
        $this->authorizeOwner($request, $todo->user_id);
        $todo->delete();
        return $this->success(null, 'Deleted');
    }

    private function authorizeOwner(Request $request, $ownerId)
    {
        abort_unless($request->user()->id === $ownerId, 403, 'Forbidden');
    }
}

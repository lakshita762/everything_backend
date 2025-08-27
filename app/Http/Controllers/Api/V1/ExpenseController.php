<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ExpenseStoreRequest;
use App\Http\Requests\V1\ExpenseUpdateRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $items = Expense::where('user_id',$request->user()->id)->latest()->paginate(20);
        return $this->success($items);
    }

    public function store(ExpenseStoreRequest $request)
    {
        $item = $request->user()->expenses()->create($request->validated());
        return $this->success($item, 'Created', 201);
    }

    public function show(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense->user_id);
        return $this->success($expense);
    }

    public function update(ExpenseUpdateRequest $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense->user_id);
        $expense->update($request->validated());
        return $this->success($expense, 'Updated');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeOwner($request, $expense->user_id);
        $expense->delete();
        return $this->success(null, 'Deleted');
    }

    private function authorizeOwner(Request $request, $ownerId)
    {
        abort_unless($request->user()->id === $ownerId, 403, 'Forbidden');
    }
}

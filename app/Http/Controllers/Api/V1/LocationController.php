<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationStoreRequest;
use App\Http\Responses\ApiResponse;
use App\Models\LocationEntry;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $items = LocationEntry::where('user_id',$request->user()->id)->latest()->paginate(20);
        return $this->success($items);
    }

    public function store(LocationStoreRequest $request)
    {
        $item = $request->user()->locationEntries()->create($request->validated());
        return $this->success($item, 'Created', 201);
    }

    public function show(Request $request, LocationEntry $location)
    {
        $this->authorizeOwner($request, $location->user_id);
        return $this->success($location);
    }

    public function destroy(Request $request, LocationEntry $location)
    {
        $this->authorizeOwner($request, $location->user_id);
        $location->delete();
        return $this->success(null, 'Deleted');
    }

    private function authorizeOwner(Request $request, $ownerId)
    {
        abort_unless($request->user()->id === $ownerId, 403, 'Forbidden');
    }
}

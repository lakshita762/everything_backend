<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationShareGroupStoreRequest;
use App\Http\Requests\V1\LocationShareGroupUpdateRequest;
use App\Http\Resources\LocationShareGroupResource;
use App\Models\LocationShareGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationShareGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()
            ->locationShareGroups()
            ->with(['members.user'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'groups' => LocationShareGroupResource::collection($groups),
            ],
        ]);
    }

    public function store(LocationShareGroupStoreRequest $request): JsonResponse
    {
        $group = $request->user()->locationShareGroups()->create($request->validated());
        $group->load('members.user');

        return response()->json([
            'data' => [
                'group' => new LocationShareGroupResource($group),
            ],
        ], 201);
    }

    public function update(LocationShareGroupUpdateRequest $request, LocationShareGroup $group): JsonResponse
    {
        $this->authorizeGroupOwner($request, $group);

        $group->fill($request->validated());
        $group->save();

        return response()->json([
            'data' => [
                'group' => new LocationShareGroupResource($group->fresh(['members.user'])),
            ],
        ]);
    }

    public function destroy(Request $request, LocationShareGroup $group): JsonResponse
    {
        $this->authorizeGroupOwner($request, $group);
        $group->delete();

        return response()->json([
            'data' => [
                'message' => 'Group deleted',
            ],
        ]);
    }

    private function authorizeGroupOwner(Request $request, LocationShareGroup $group): void
    {
        abort_unless($request->user()->id === $group->owner_id, 403, 'Forbidden');
    }
}

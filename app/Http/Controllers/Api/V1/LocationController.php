<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationStoreRequest;
use App\Http\Resources\LocationEntryResource;
use App\Models\LocationEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locationsQuery = $request->user()->locationEntries()->latest();

        $tag = trim((string) $request->query('tag', ''));
        if ($tag !== '') {
            $locationsQuery->where(function ($query) use ($tag) {
                $query->where('tag', $tag)->orWhere('title', $tag);
            });
        }

        $locations = $locationsQuery->get();

        return response()->json([
            'data' => [
                'locations' => LocationEntryResource::collection($locations),
            ],
        ]);
    }

    public function store(LocationStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tag = $data['tag'] ?? $data['label'] ?? 'Pinned Location';

        $entry = $request->user()->locationEntries()->create([
            'title' => $data['label'] ?? $tag,
            'tag' => $tag,
            'latitude' => $data['lat'],
            'longitude' => $data['lng'],
        ]);

        return response()->json([
            'data' => [
                'location' => new LocationEntryResource($entry),
            ],
        ], 201);
    }

    public function show(Request $request, LocationEntry $location): JsonResponse
    {
        $this->authorizeOwner($request, $location->user_id);

        return response()->json([
            'data' => [
                'location' => new LocationEntryResource($location),
            ],
        ]);
    }

    public function destroy(Request $request, LocationEntry $location): JsonResponse
    {
        $this->authorizeOwner($request, $location->user_id);
        $location->delete();

        return response()->json([
            'data' => [
                'message' => 'Location removed',
            ],
        ]);
    }

    private function authorizeOwner(Request $request, int $ownerId): void
    {
        abort_unless($request->user()->id === $ownerId, 403, 'Forbidden');
    }
}

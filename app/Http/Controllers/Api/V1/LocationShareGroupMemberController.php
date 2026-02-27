<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationShareGroupMemberStoreRequest;
use App\Http\Resources\LocationShareGroupMemberResource;
use App\Models\LocationShareGroup;
use App\Models\LocationShareGroupMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationShareGroupMemberController extends Controller
{
    public function store(LocationShareGroupMemberStoreRequest $request, LocationShareGroup $group): JsonResponse
    {
        $this->authorizeGroupOwner($request, $group);

        $payload = $request->validated();
        $email = strtolower(trim($payload['email']));
        $role = $payload['role'] ?? 'viewer';

        $memberUser = User::where('email', $email)->first();

        $member = LocationShareGroupMember::query()->updateOrCreate(
            [
                'location_share_group_id' => $group->id,
                'email' => $email,
            ],
            [
                'user_id' => $memberUser?->id,
                'role' => $role,
            ]
        );
        $member->load('user');

        return response()->json([
            'data' => [
                'member' => new LocationShareGroupMemberResource($member),
            ],
        ], 201);
    }

    public function destroy(Request $request, LocationShareGroup $group, LocationShareGroupMember $member): JsonResponse
    {
        $this->authorizeGroupOwner($request, $group);
        abort_unless($member->location_share_group_id === $group->id, 422, 'Member does not belong to this group.');

        $member->delete();

        return response()->json([
            'data' => [
                'message' => 'Group member removed',
            ],
        ]);
    }

    private function authorizeGroupOwner(Request $request, LocationShareGroup $group): void
    {
        abort_unless($request->user()->id === $group->owner_id, 403, 'Forbidden');
    }
}

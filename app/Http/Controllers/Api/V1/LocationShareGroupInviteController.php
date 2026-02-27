<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationParticipantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationShareParticipantResource;
use App\Models\LocationShare;
use App\Models\LocationShareGroup;
use App\Models\LocationShareParticipant;
use App\Models\User;
use App\Services\Smtp2GoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationShareGroupInviteController extends Controller
{
    public function store(Request $request, LocationShare $share, LocationShareGroup $group): JsonResponse
    {
        abort_unless($request->user()->id === $share->owner_id, 403, 'Forbidden');
        abort_unless($request->user()->id === $group->owner_id, 403, 'Forbidden');

        $group->load('members');
        $share->loadMissing('owner');

        $createdIds = [];
        $skippedEmails = [];

        foreach ($group->members as $groupMember) {
            $email = strtolower(trim($groupMember->email));
            if ($email === '' || strcasecmp($email, $share->owner?->email ?? '') === 0) {
                $skippedEmails[] = $email;
                continue;
            }

            $user = User::where('email', $email)->first();
            $participant = LocationShareParticipant::query()
                ->where('location_share_id', $share->id)
                ->where(function ($query) use ($user, $email) {
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }
                    $query->orWhere('email', $email);
                })
                ->first();

            if (!$participant) {
                $participant = new LocationShareParticipant([
                    'location_share_id' => $share->id,
                ]);
            }

            $participant->fill([
                'user_id' => $user?->id,
                'email' => $email,
                'role' => $groupMember->role,
                'status' => LocationParticipantStatus::PENDING->value,
                'invited_at' => now(),
                'responded_at' => null,
            ]);
            $participant->save();
            $participant->load('user');

            $createdIds[] = $participant->id;
            $this->sendInviteEmail($share, $participant);
        }

        $participants = LocationShareParticipant::query()
            ->whereIn('id', $createdIds)
            ->with('user')
            ->get();

        Log::info('location_share.group_invited', [
            'share_id' => $share->id,
            'group_id' => $group->id,
            'participants_count' => $participants->count(),
            'skipped_count' => count($skippedEmails),
        ]);

        return response()->json([
            'data' => [
                'participants' => LocationShareParticipantResource::collection($participants),
                'skipped_emails' => array_values(array_filter($skippedEmails)),
            ],
        ]);
    }

    private function sendInviteEmail(LocationShare $share, LocationShareParticipant $participant): void
    {
        try {
            $owner = $share->owner;
            $subject = sprintf(
                '%s invited you to a location share%s',
                $owner?->name ?? 'A user',
                $share->name ? (': "' . $share->name . '"') : ''
            );

            $html = view('emails.location_share_invite', [
                'share' => $share,
                'participant' => $participant,
                'owner' => $owner,
            ])->render();

            app(Smtp2GoService::class)->send(
                to: $participant->email,
                subject: $subject,
                htmlBody: $html
            );
        } catch (\Throwable $e) {
            Log::warning('location_share.group_invite_email_failed', [
                'share_id' => $share->id,
                'participant_id' => $participant->id,
                'email' => $participant->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

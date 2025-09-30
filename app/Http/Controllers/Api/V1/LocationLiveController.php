<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationShareStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocationLiveUpdateRequest;
use App\Http\Resources\LocationPointResource;
use App\Http\Resources\LocationShareResource;
use App\Models\LocationPoint;
use App\Models\LocationShare;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationLiveController extends Controller
{
    public function store(LocationLiveUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $share = LocationShare::findOrFail($data['share_id']);

        $this->authorize('pushLive', $share);
        $this->guardShareIsActive($share);

        $recordedAt = isset($data['recorded_at'])
            ? Carbon::parse($data['recorded_at'])
            : now();

        $point = $share->points()->create([
            'user_id' => $request->user()->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'recorded_at' => $recordedAt,
        ]);

        $point->load('user');
        $share->load(['owner', 'latestPoint']);

        Log::info('location_share.point_created', [
            'share_id' => $share->id,
            'point_id' => $point->id,
        ]);

        return response()->json([
            'data' => [
                'point' => new LocationPointResource($point),
                'share' => new LocationShareResource($share),
            ],
        ], 201);
    }

    public function stream(Request $request, string $sessionToken): StreamedResponse
    {
        $share = LocationShare::where('session_token', $sessionToken)->firstOrFail();
        $this->authorize('stream', $share);
        $this->guardShareIsActive($share, allowStopped: true);

        $lastId = 0;
        $isTesting = App::environment('testing');

        return response()->stream(function () use ($share, &$lastId, $isTesting) {
            $loop = 0;
            while ($loop < 300) {
                $share->refresh('status');
                if ($share->status?->value === LocationShareStatus::STOPPED->value) {
                    echo "event: ended\ndata: {}\n\n";
                    @ob_flush();
                    @flush();
                    break;
                }

                $points = LocationPoint::where('location_share_id', $share->id)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->get();

                foreach ($points as $point) {
                    $lastId = $point->id;
                    $payload = json_encode([
                        'lat' => (float) $point->lat,
                        'lng' => (float) $point->lng,
                        'recorded_at' => $point->recorded_at?->toIso8601String(),
                    ]);

                    echo "data: {$payload}\n\n";
                    @ob_flush();
                    @flush();
                }

                if ($isTesting || connection_aborted()) {
                    break;
                }

                $loop++;
                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    private function guardShareIsActive(LocationShare $share, bool $allowStopped = false): void
    {
        if ($share->expires_at && now()->greaterThan($share->expires_at)) {
            $share->status = LocationShareStatus::STOPPED->value;
            $share->save();
        }

        if (!$allowStopped && $share->status?->value === LocationShareStatus::STOPPED->value) {
            abort(410, 'Share is no longer active');
        }
    }
}

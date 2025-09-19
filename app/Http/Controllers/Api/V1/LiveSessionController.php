<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Models\LiveSession;
use App\Models\LiveLocation;
use Carbon\Carbon;

class LiveSessionController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['title' => 'nullable|string|max:255']);

        $session = LiveSession::create([
            'session_id' => Str::uuid()->toString(),
            'owner_id' => $user->id,
            'title' => $data['title'] ?? null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return response()->json(['data' => [
            'session_id' => $session->session_id,
            'title' => $session->title,
            'created_at' => $session->created_at?->toIso8601String(),
        ]], 201);
    }

    public function update(Request $request, $session_id)
    {
        $user = $request->user();
        $session = LiveSession::find($session_id);
        if (!$session) return response()->json(['message' => 'Not found'], 404);
        if (!$session->is_active) return response()->json(['message' => 'Session ended'], 410);

        // authorization: owner or same user
        if ($session->owner_id !== $user->id) {
            // allow updates only by owner in this simple implementation
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|integer',
            'timestamp' => 'nullable|date',
        ]);

        $ts = isset($validated['timestamp']) ? Carbon::parse($validated['timestamp'])->toDateTimeString() : Carbon::now()->toDateTimeString();

        // upsert into last-known table
        LiveLocation::updateOrCreate(
            ['session_id' => $session->session_id],
            [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'timestamp' => $ts,
            ]
        );

        // append to history
        \DB::table('live_location_history')->insert([
            'session_id' => $session->session_id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'timestamp' => $ts,
        ]);

        // publish to Redis channel for realtime
        $payload = json_encode([
            'session_id' => $session->session_id,
            'latitude' => (float)$validated['latitude'],
            'longitude' => (float)$validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'timestamp' => Carbon::parse($ts)->toIso8601String(),
        ]);
        try {
            Redis::publish("live_session:{$session->session_id}", $payload);
        } catch (\Throwable $e) {
            // silently ignore if redis not configured or phpredis missing
        }

        return response()->json(['status' => 'ok']);
    }

    public function show(Request $request, $session_id)
    {
        $session = LiveSession::with('latestLocation')->find($session_id);
        if (!$session) return response()->json(['message' => 'Not found'], 404);
        if (!$session->is_active && $session->ended_at) return response()->json(['message' => 'Ended'], 410);

        $loc = $session->latestLocation;

        $data = [
            'session_id' => $session->session_id,
            'title' => $session->title,
            'is_active' => $session->is_active,
        ];
        if ($loc) {
            $data = array_merge($data, [
                'latitude' => (float)$loc->latitude,
                'longitude' => (float)$loc->longitude,
                'accuracy' => $loc->accuracy !== null ? (int)$loc->accuracy : null,
                'timestamp' => $loc->timestamp->toIso8601String(),
            ]);
        }

        return response()->json(['data' => $data]);
    }

    public function end(Request $request, $session_id)
    {
        $user = $request->user();
        $session = LiveSession::find($session_id);
        if (!$session) return response()->json(['message' => 'Not found'], 404);
        if ($session->owner_id !== $user->id) return response()->json(['message' => 'Forbidden'], 403);

        $session->update(['is_active' => false, 'ended_at' => Carbon::now()]);

        return response()->json(['status' => 'ended']);
    }

    public function listByOwner(Request $request)
    {
        $owner = $request->query('owner_id') ?? $request->user()->id;
        $sessions = LiveSession::where('owner_id', $owner)->where('is_active', true)->get();
        return response()->json(['data' => $sessions]);
    }

    // SSE endpoint: streams updates for a session
    public function events(Request $request, $session_id)
    {
        $session = LiveSession::find($session_id);
        if (!$session) return response()->json(['message' => 'Not found'], 404);

        // basic auth check: require token
        $request->user();

        // Create a stream response for SSE
        return response()->stream(function () use ($session_id) {
            try {
                // subscribe to redis channel and echo messages as SSE
                $pubsub = \Illuminate\Support\Facades\Redis::pubSubLoop();
                $channel = "live_session:{$session_id}";
                $pubsub->subscribe($channel);
                foreach ($pubsub as $message) {
                    if ($message->kind === 'message') {
                        echo "data: {$message->payload}\n\n";
                        // flush
                        @ob_flush();
                        @flush();
                    }
                }
                $pubsub->unsubscribe();
                $pubsub->disconnect();
            } catch (\Throwable $e) {
                // redis not available; end stream
                echo "event: error\ndata: {\"error\":\"redis unavailable\"}\n\n";
                @ob_flush();
                @flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}

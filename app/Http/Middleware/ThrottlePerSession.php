<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class ThrottlePerSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $sessionId = $request->route('session_id');
        if (!$user || !$sessionId) return $next($request);

        $key = "live_update:{$sessionId}:user:{$user->id}";
        $allowed = Cache::add($key, true, 1); // 1 second
        if (!$allowed) {
            return response()->json(['message' => 'Too many requests'], 429);
        }
        return $next($request);
    }
}

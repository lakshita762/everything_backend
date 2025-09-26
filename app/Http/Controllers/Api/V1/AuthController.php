<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','unique:users,email'],
            'password' => ['required','string','min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var \App\Models\User $user */
        $user = User::where('email', $validated['email'])->firstOrFail();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function google(Request $request)
    {
        $validated = $request->validate([
            'id_token' => ['required','string'],
        ]);

        $clientId = env('GOOGLE_CLIENT_ID');
        $googleClient = new GoogleClient(['client_id' => $clientId]);

        $payload = $googleClient->verifyIdToken($validated['id_token']);
        if (!$payload) {
            return response()->json(['message' => 'Invalid Google ID token'], 400);
        }

        if (($payload['aud'] ?? null) !== $clientId) {
            return response()->json(['message' => 'Invalid Google token audience'], 400);
        }

        if (!($payload['email_verified'] ?? false)) {
            return response()->json(['message' => 'Email not verified by Google'], 400);
        }

        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? ($payload['given_name'] ?? 'User');

        if (!$googleId || !$email) {
            return response()->json(['message' => 'Google token missing required claims'], 400);
        }

        DB::beginTransaction();
        try {
            $user = User::where('google_id', $googleId)->first();
            if (!$user) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->google_id = $googleId;
                    $user->email_verified_at = $user->email_verified_at ?: now();
                    $user->save();
                } else {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)),
                        'google_id' => $googleId,
                        'email_verified_at' => now(),
                    ]);
                }
            }

            $token = $user->createToken('api')->plainTextToken;
            DB::commit();

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Server error while logging in with Google'], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}

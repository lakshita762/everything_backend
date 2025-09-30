<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationParticipantStatus;
use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationShareParticipantResource;
use App\Http\Resources\LocationShareResource;
use App\Http\Resources\TodoListInviteResource;
use App\Http\Resources\TodoListResource;
use App\Http\Resources\UserSummaryResource;
use App\Models\LocationShare;
use App\Models\LocationShareParticipant;
use App\Models\TodoList;
use App\Models\TodoListInvite;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const GOOGLE_ISSUERS = [
        'https://accounts.google.com',
        'accounts.google.com',
    ];

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'last_login_at' => now(),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return $this->buildAuthResponse($user->fresh(), $token, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('web')->attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid credentials',
                ],
            ], 401);
        }

        /** @var User $user */
        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('api')->plainTextToken;

        return $this->buildAuthResponse($user->fresh(), $token);
    }

    public function google(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $clientId = (string) config('services.google.client_id', env('GOOGLE_CLIENT_ID'));
        $googleClient = app(GoogleClient::class);
        if (method_exists($googleClient, 'setClientId')) {
            $googleClient->setClientId($clientId);
        }

        $payload = $googleClient->verifyIdToken($validated['id_token']);
        if (!$payload) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid Google ID token',
                ],
            ], 400);
        }

        if (($payload['aud'] ?? null) !== $clientId) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid Google token audience',
                ],
            ], 400);
        }

        if (!in_array($payload['iss'] ?? '', self::GOOGLE_ISSUERS, true)) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid Google token issuer',
                ],
            ], 400);
        }

        if (!($payload['email_verified'] ?? false)) {
            return response()->json([
                'error' => [
                    'message' => 'Email not verified by Google',
                ],
            ], 400);
        }

        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? ($payload['given_name'] ?? null);
        $avatar = $payload['picture'] ?? null;

        if (!$googleId || !$email) {
            return response()->json([
                'error' => [
                    'message' => 'Google token missing required claims',
                ],
            ], 400);
        }

        try {
            $user = DB::transaction(function () use ($googleId, $email, $name, $avatar) {
                /** @var User|null $user */
                $user = User::where('google_id', $googleId)->first();

                if (!$user) {
                    $user = User::where('email', $email)->first();
                }

                $attributes = [
                    'google_id' => $googleId,
                    'name' => $name ?: 'Google User',
                    'email_verified_at' => now(),
                    'avatar_url' => $avatar,
                    'last_login_at' => now(),
                ];

                if ($user) {
                    $user->forceFill($attributes);
                    $user->save();
                } else {
                    $user = User::create([
                        'name' => $attributes['name'],
                        'email' => $email,
                        'password' => Hash::make(Str::random(64)),
                        'google_id' => $googleId,
                        'avatar_url' => $avatar,
                        'email_verified_at' => now(),
                        'last_login_at' => now(),
                    ]);
                }

                return $user;
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => [
                    'message' => 'Server error while logging in with Google',
                ],
            ], 500);
        }

        $token = $user->createToken('api')->plainTextToken;

        return $this->buildAuthResponse($user->fresh(), $token);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserSummaryResource($request->user()->fresh()),
            ],
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $user = $request->user();

        $todoLists = TodoList::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', TodoListMembershipStatus::ACCEPTED->value);
            })
            ->with(['owner', 'tasks.assignee', 'memberships.user'])
            ->orderByDesc('updated_at')
            ->get();

        $todoInvites = TodoListInvite::query()
            ->where('email', $user->email)
            ->where('status', TodoListInviteStatus::PENDING->value)
            ->with(['list.owner'])
            ->orderByDesc('invited_at')
            ->get();

        $locationOutgoing = LocationShare::query()
            ->where('owner_id', $user->id)
            ->with(['owner', 'latestPoint'])
            ->orderByDesc('updated_at')
            ->get();

        $locationIncoming = LocationShare::query()
            ->where('owner_id', '!=', $user->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', LocationParticipantStatus::ACCEPTED->value);
            })
            ->with(['owner', 'latestPoint'])
            ->orderByDesc('updated_at')
            ->get();

        $locationInvites = LocationShareParticipant::query()
            ->where('status', LocationParticipantStatus::PENDING->value)
            ->where(function ($query) use ($user) {
                $query->where('email', $user->email)
                    ->orWhere('user_id', $user->id);
            })
            ->with(['share.owner'])
            ->orderByDesc('invited_at')
            ->get();

        return response()->json([
            'data' => [
                'user' => new UserSummaryResource($user),
                'todo' => [
                    'lists' => TodoListResource::collection($todoLists),
                    'invites' => TodoListInviteResource::collection($todoInvites),
                ],
                'location' => [
                    'outgoing' => LocationShareResource::collection($locationOutgoing),
                    'incoming' => LocationShareResource::collection($locationIncoming),
                    'invites' => LocationShareParticipantResource::collection($locationInvites),
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'data' => [
                'message' => 'Logged out',
            ],
        ]);
    }

    private function buildAuthResponse(User $user, string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserSummaryResource($user),
            ],
        ], $status);
    }
}


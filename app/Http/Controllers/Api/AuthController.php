<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use WorkOS\SSO;

class AuthController extends Controller
{
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->fresh(),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        /** @var User $user */
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->fresh(),
        ], 201);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 422);
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->firstOrFail();
        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->fresh(),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(null, 204);
    }

    public function getRedirectUrl(Request $request)
    {
        $sso = new SSO();
        $redirectUrl = config('services.workos.app_redirect_url');
        $provider = $request->provider ?? 'GoogleOAuth';

        $authorizationUrl = $sso->getAuthorizationUrl(
            null,
            $redirectUrl,
            ['state' => 'your_state_value'],
            $provider,
            null,
            null,
            null,
            null
        );

        return response()->json(['redirect_url' => $authorizationUrl]);
    }

    public function handleCallback(Request $request)
    {
        try {
            \Log::info('Received callback with code: ' . $request->code);
            $sso = new SSO();
            $profileAndToken = $sso->getProfileAndToken($request->code);

            \Log::info('Successfully got profile from WorkOS');

            $user = User::updateOrCreate(
                ['email' => $profileAndToken->profile->email],
                [
                    'name' => $profileAndToken->profile->firstName . ' ' . $profileAndToken->profile->lastName,
                    'workos_id' => $profileAndToken->profile->id,
                    'avatar' => $profileAndToken->profile->profilePictureUrl ?? '',
                ]
            );

            $token = $user->createToken('auth-token')->plainTextToken;

            \Log::info('Authentication successful for user: ' . $user->email);

            $activeQuestions = QuestionnaireQuestion::where('is_active', true)->count();
            $userAnsweredQuestions = QuestionnaireResponse::where('user_id', $user->id)
                ->distinct('question_id')
                ->count('question_id');

            $hasFilledQuestionnaire = ($userAnsweredQuestions > 0 && $userAnsweredQuestions >= $activeQuestions);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'has_filled_questionnaire' => $hasFilledQuestionnaire,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Authentication error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

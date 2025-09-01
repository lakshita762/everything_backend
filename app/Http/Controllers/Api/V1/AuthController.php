<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;


class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email'=> ['required','email','unique:users,email'],
            'password'=> ['required', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
            'role'=>'user',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;
        return $this->success(['token'=>$token,'user'=>$user], 'Registered', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'=>['required','email'],
            'password'=>['required'],
        ]);

        $user = User::where('email',$data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error('Invalid credentials', 422);
        }

        $token = $user->createToken('mobile')->plainTextToken;
        $this->loadUserData($user);
        return $this->success(['token'=>$token,'user'=>$user], 'Logged in');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $this->loadUserData($user);
        return $this->success(['user'=>$user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return $this->success(null, 'Logged out');
    }

    /**
     * Load user's data on demand
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadData(Request $request)
    {
        $user = $request->user();
        $this->loadUserData($user);
        return $this->success(['user' => $user], 'User data loaded');
    }

    /**
     * Load user's todos, expenses, and location entries
     * 
     * @param User $user
     * @return void
     */
    private function loadUserData(User $user): void
    {
        $limits = config('user_data.limits', [
            'todos' => 100,
            'expenses' => 100,
            'location_entries' => 100
        ]);
        
        $user->load([
            'todos' => function($query) use ($limits) { 
                $query->latest()->take($limits['todos']); 
            }, 
            'expenses' => function($query) use ($limits) { 
                $query->latest()->take($limits['expenses']); 
            }, 
            'locationEntries' => function($query) use ($limits) { 
                $query->latest()->take($limits['location_entries']); 
            }
        ]);
    }
}
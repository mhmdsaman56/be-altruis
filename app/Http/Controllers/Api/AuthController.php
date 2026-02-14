<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Laravel\Socialite\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Google_Client;



class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful',
            'payload' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        try {
            //code...
            $user = User::create(
                [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]
            );
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'User registered successfully',
                'payload' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => 'Registration failed',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {


        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }


    public function loginWithGoogle(Request $request)
    {
        try {

            $client = new Google_Client([
                'client_id' => env('GOOGLE_CLIENT_ID'),
            ]);

        $payload = $client->verifyIdToken($request->token);
        if (!$payload) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
        $user = User::firstOrCreate(
            ['email' => $payload['email']],
            ['name' => $payload['name'], 'password' => Hash::make(Str::random(32))]
        );
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful',
            'payload' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
        }catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message' => 'Google login failed', 'error' => $th->getMessage()], 500);
        }

    }
}

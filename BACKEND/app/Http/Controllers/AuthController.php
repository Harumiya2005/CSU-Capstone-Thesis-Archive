<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // --- LOGIN FOR BOTH ADMIN & STUDENT ---
    public function login(Request $request)
    {
        // 1. Validate Input
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Find User
        $user = User::where('identifier', $request->identifier)->first();

        // 3. Check Password & User Existence
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 4. Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'role' => $user->role,
            'name' => $user->name // Pass role to frontend for routing
        ], 200);
    }



    // --- LOGOUT ---
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}

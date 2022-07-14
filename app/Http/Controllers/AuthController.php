<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create admin user.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string',
        ]);

        // Persist admin details to the database
        $user = User::create([
            'first_name' => $validatedData['first_name'] ?? null,
            'last_name' => $validatedData['last_name'] ?? null,
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'is_admin' => true,
        ]);

        // Create access token
        $token = $user->createToken('api_token');

        return response()->json([
            'status' => true,
            'message' => 'Admin created',
            'data' => [
                'user' => $user,
                'token' => $token->plainTextToken,
            ],
        ], 201);
    }

    /**
     * Log admin user in.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validatedData['email'])->first();

        // Ensure user supplied valid credentails
        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.',
                'data' => null,
            ], 401);
        }

        // Create access token
        $token = $user->createToken('api_token');

        return response()->json([
            'status' => true,
            'message' => 'Logged in.',
            'data' => [
                'token' => $token->plainTextToken,
            ],
        ]);
    }

    /**
     * Log admin user out.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out.',
            'data' => null,
        ]);
    }
}

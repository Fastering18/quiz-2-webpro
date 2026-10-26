<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and issue personal access token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with(['mahasiswa.dosenWali.user', 'dosen'])
            ->where('username', $validated['username'])
            ->orWhere('email', $validated['username'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'username' => ['Kombinasi username/email dan password tidak sesuai.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Get the authenticated user with role-specific details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil. Sesi telah diakhiri.',
        ]);
    }
}

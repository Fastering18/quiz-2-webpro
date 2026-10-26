<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);

        return response()->json([
            'profile' => $user,
        ]);
    }

    /**
     * Update user contact info and photo.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'alamat_rumah' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_profil_key' => ['nullable', 'string'],
        ]);

        $dataToUpdate = [
            'nomor_telepon' => $validated['nomor_telepon'] ?? $user->nomor_telepon,
            'alamat_rumah' => $validated['alamat_rumah'] ?? $user->alamat_rumah,
        ];

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('avatars', 'public');
            $dataToUpdate['foto_profil_key'] = $path;
        } elseif (isset($validated['foto_profil_key'])) {
            $dataToUpdate['foto_profil_key'] = $validated['foto_profil_key'];
        }

        $user->update($dataToUpdate);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'profile' => $user->fresh(['mahasiswa.dosenWali.user', 'dosen']),
        ]);
    }

    /**
     * Change user account password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini yang dimasukkan tidak cocok.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }
}

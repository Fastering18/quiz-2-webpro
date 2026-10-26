<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    private const EXPIRE_MINUTES = 60;

    private const MAX_ATTEMPTS = 5; // per hour, per email+IP

    private const GENERIC_MESSAGE = 'Jika akun terdaftar, instruksi pengaturan ulang kata sandi telah dikirim ke email yang terkait.';

    /**
     * Request a password reset mail. Always answers with the same message.
     */
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required_without:username', 'nullable', 'string', 'max:120'],
            'username' => ['required_without:email', 'nullable', 'string', 'max:120'],
        ]);

        $identifier = strtolower(trim((string) ($validated['email'] ?? $validated['username'])));
        $key = 'forgot:'.sha1($identifier.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.',
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        try {
            $user = User::whereRaw('LOWER(email) = ?', [$identifier])
                ->orWhereRaw('LOWER(username) = ?', [$identifier])
                ->first();

            if ($user) {
                $token = Str::random(48);
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($token), 'created_at' => now()],
                );

                $base = rtrim((string) config('app.frontend_url'), '/');
                $url = $base.'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->email]);

                Mail::to($user->email)->send(new ResetPasswordMail(
                    $user->nama_lengkap, $token, $user->email, $url, self::EXPIRE_MINUTES,
                ));
            }
        } catch (\Throwable $e) {
            // Never leak failures (SMTP down, etc.) to the client; response stays identical.
            Log::error('Forgot-password mail failed: '.$e->getMessage());
        }

        return response()->json(['message' => self::GENERIC_MESSAGE]);
    }

    /**
     * Set a new password using the emailed token.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = strtolower($validated['email']);
        $key = 'reset:'.sha1($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Terlalu banyak percobaan. Coba lagi nanti.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $invalid = ValidationException::withMessages([
            'token' => ['Token tidak valid atau sudah kedaluwarsa.'],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $row = $user ? DB::table('password_reset_tokens')->where('email', $user->email)->first() : null;

        if (! $row || ! Hash::check($validated['token'], $row->token)
            || Carbon::parse($row->created_at)->addMinutes(self::EXPIRE_MINUTES)->isPast()) {
            throw $invalid;
        }

        DB::transaction(function () use ($user, $validated) {
            $user->update(['password_hash' => Hash::make($validated['password'])]);
            DB::table('password_reset_tokens')->where('email', $user->email)->delete(); // single use
            $user->tokens()->delete(); // revoke all Sanctum tokens
        });
        RateLimiter::clear($key);

        return response()->json(['message' => 'Kata sandi berhasil diatur ulang. Silakan login dengan kata sandi baru.']);
    }
}

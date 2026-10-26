<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        RateLimiter::clear('x');
        config(['app.frontend_url' => 'https://front.example']);
    }

    private function requestToken(User $user): ResetPasswordMail
    {
        Mail::fake();
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $sent = null;
        Mail::assertSent(ResetPasswordMail::class, function ($m) use (&$sent) {
            $sent = $m;

            return true;
        });

        return $sent;
    }

    public function test_forgot_sends_mail_with_link_and_token_and_same_response_for_unknown(): void
    {
        Mail::fake();
        $user = User::where('role', 'mahasiswa')->first();

        $known = $this->postJson('/api/forgot-password', ['email' => strtoupper($user->email)]);
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'tidak.ada@example.com']);
        $byUsername = $this->postJson('/api/forgot-password', ['username' => $user->username]);

        $known->assertOk();
        $this->assertSame($known->json(), $unknown->json());
        $this->assertSame($known->json(), $byUsername->json());
        Mail::assertSent(ResetPasswordMail::class, 2);
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $m) use ($user) {
            return $m->hasTo($user->email)
                && str_starts_with($m->resetUrl, 'https://front.example/reset-password?token=')
                && str_contains($m->resetUrl, urlencode($user->email))
                && str_contains($m->resetUrl, $m->token)
                && str_contains($m->render(), $m->token);
        });
        $this->assertSame(1, DB::table('password_reset_tokens')->where('email', $user->email)->count());
        $this->assertNotSame($m = DB::table('password_reset_tokens')->value('token'), null);
        $this->assertStringStartsWith('$2y$', $m); // hashed, not plain
    }

    public function test_forgot_requires_identifier_and_is_throttled(): void
    {
        $this->postJson('/api/forgot-password', [])->assertStatus(422);

        Mail::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/forgot-password', ['email' => 'a@example.com'])->assertOk();
        }
        $this->postJson('/api/forgot-password', ['email' => 'a@example.com'])->assertStatus(429);
        // another address is not affected
        $this->postJson('/api/forgot-password', ['email' => 'b@example.com'])->assertOk();
    }

    public function test_mail_failure_does_not_change_response(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $user = User::where('role', 'dosen')->first();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);
    }

    public function test_reset_flow_single_use_and_revokes_tokens(): void
    {
        $user = User::where('role', 'mahasiswa')->first();
        $user->createToken('old');
        $mail = $this->requestToken($user);

        $payload = [
            'email' => $user->email, 'token' => $mail->token,
            'password' => 'BaruPass#2026', 'password_confirmation' => 'BaruPass#2026',
        ];
        $this->postJson('/api/reset-password', $payload)->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('BaruPass#2026', $user->password_hash));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());

        // single use
        $this->postJson('/api/reset-password', $payload)->assertStatus(422)->assertJsonValidationErrors('token');
        $this->postJson('/api/login', ['username' => $user->username, 'password' => 'BaruPass#2026'])->assertOk();
    }

    public function test_reset_rejects_wrong_token_expired_token_and_weak_or_unconfirmed_password(): void
    {
        $user = User::where('role', 'mahasiswa')->first();
        $hash = $user->password_hash;
        $mail = $this->requestToken($user);
        $ok = ['password' => 'BaruPass#2026', 'password_confirmation' => 'BaruPass#2026'];

        $this->postJson('/api/reset-password', ['email' => $user->email, 'token' => 'salah'] + $ok)
            ->assertStatus(422)->assertJsonValidationErrors('token');
        $this->postJson('/api/reset-password', ['email' => 'nobody@example.com', 'token' => $mail->token] + $ok)
            ->assertStatus(422)->assertJsonValidationErrors('token');
        $this->postJson('/api/reset-password', ['email' => $user->email, 'token' => $mail->token, 'password' => 'BaruPass#2026', 'password_confirmation' => 'beda'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);
        $this->postJson('/api/reset-password', ['email' => $user->email, 'token' => $mail->token] + $ok)
            ->assertStatus(422)->assertJsonValidationErrors('token');

        // the password was not changed by any failed attempt
        $this->assertSame($hash, $user->fresh()->password_hash);
    }

    public function test_reset_page_renders(): void
    {
        $this->get('/reset-password?token=abc&email=a@example.com')->assertOk()->assertSee('Atur ulang kata sandi');
    }
}

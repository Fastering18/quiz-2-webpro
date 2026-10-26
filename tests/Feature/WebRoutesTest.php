<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_view_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Selamat datang di myITS Classroom');
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $protectedRoutes = ['/portal', '/classroom', '/library', '/krs', '/lapor-admin', '/profile', '/admin-panel'];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/');
        }
    }

    public function test_authenticated_user_is_redirected_to_portal_from_root(): void
    {
        $user = User::where('role', 'mahasiswa')->orderBy('user_id')->first();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('portal'));
    }

    public function test_authenticated_user_can_access_all_dashboard_views(): void
    {
        $user = User::where('role', 'admin')->orderBy('user_id')->first();

        $views = [
            '/portal' => 'portal',
            '/classroom' => 'classroom',
            '/library' => 'library',
            '/krs' => 'krs',
            '/lapor-admin' => 'lapor-admin',
            '/profile' => 'profile',
            '/admin-panel' => 'admin-panel',
        ];

        foreach ($views as $uri => $viewName) {
            $response = $this->actingAs($user)->get($uri);
            $response->assertStatus(200);
            $response->assertViewIs($viewName);
        }
    }

    public function test_authenticated_user_can_logout_via_web(): void
    {
        $user = User::where('role', 'mahasiswa')->orderBy('user_id')->first();

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_user_can_logout_via_api(): void
    {
        $loginRes = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin',
        ])->assertStatus(200);

        $token = $loginRes->json('access_token');
        $this->assertAuthenticated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Logout berhasil. Sesi telah diakhiri.');

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // In tests we don't need cookie encryption, and disabling it keeps auth cookie values stable.
        $this->withoutMiddleware(EncryptCookies::class);
    }

    public function test_login_sets_access_token_cookie_and_me_requires_jwt(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        // Without JWT cookie
        $this->getJson('/api/items')->assertStatus(401);

        // Login (API routes use `web` middleware, so attach CSRF token)
        $csrf = $this->getCsrfToken();
        $res = $this->withHeader('X-CSRF-TOKEN', $csrf)->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.email', $user->email)
            ->assertCookie('access_token');

        $jwtCookieValue = null;
        foreach ($res->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'access_token') {
                $jwtCookieValue = $cookie->getValue();
                break;
            }
        }
        $this->assertNotNull($jwtCookieValue);
    }

    private function getCsrfToken(): string
    {
        $res = $this->get('/api/csrf-cookie');

        $token = null;
        foreach ($res->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN') {
                $token = $cookie->getValue();
                break;
            }
        }

        $this->assertNotNull($token);

        return (string) $token;
    }
}


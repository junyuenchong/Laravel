<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\JwtCookieAuth;
use Tests\TestCase;

class ItemsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // In tests we don't need cookie encryption, and disabling it keeps auth cookie values stable.
        $this->withoutMiddleware(EncryptCookies::class);
    }

    public function test_items_crud_and_search_and_cursor_pagination(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('secret1234'),
        ]);

        // For CRUD integration we authenticate via the session guard.
        // JWT cookie auth is exercised separately in E2E.
        $this->withoutMiddleware(JwtCookieAuth::class);
        $this->actingAs($user);

        // Create
        $csrf = $this->getCsrfToken();
        $create = $this->withHeader('X-CSRF-TOKEN', $csrf)->postJson('/api/items', [
            'name' => 'Apple iPhone',
            'sku' => 'SKU-IPHN-001',
            'description' => 'Phone',
            'price_cents' => 99900,
            'is_active' => true,
        ])->assertStatus(201);

        $this->withHeader('X-CSRF-TOKEN', $csrf)->postJson('/api/items', [
            'name' => 'Google Pixel',
            'sku' => 'SKU-PIXL-001',
            'description' => 'Phone',
            'price_cents' => 79900,
            'is_active' => true,
        ])->assertStatus(201);

        $id = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $id);

        // Read
        $this->getJson("/api/items/{$id}")
            ->assertOk()
            ->assertJsonPath('data.sku', 'SKU-IPHN-001');

        // Update
        $this->putJson("/api/items/{$id}", ['name' => 'Apple iPhone Pro'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Apple iPhone Pro');

        // Search by sku exact
        $this->getJson('/api/items?q=SKU-IPHN-001&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Cursor pagination
        $page1 = $this->getJson('/api/items?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $cursor = $page1->json('next_cursor');
        $this->assertNotEmpty($cursor);

        $this->getJson('/api/items?per_page=1&cursor='.$cursor)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Delete
        $this->withHeader('X-CSRF-TOKEN', $csrf)->deleteJson("/api/items/{$id}")
            ->assertStatus(204);
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


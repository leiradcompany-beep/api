<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class CleanerAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test blocked access without token.
     */
    public function test_access_blocked_without_token()
    {
        $response = $this->getJson('/api/cleaner/dashboard');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthenticated. Valid Bearer token required.',
                     'error_code' => 'AUTH_REQUIRED'
                 ]);
    }

    /**
     * Test access blocked with invalid token.
     */
    public function test_access_blocked_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/cleaner/dashboard');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthenticated. Valid Bearer token required.',
                     'error_code' => 'AUTH_REQUIRED'
                 ]);
    }

    /**
     * Test access blocked for non-cleaner user (insufficient permissions).
     */
    public function test_access_blocked_for_non_cleaner()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/cleaner/dashboard');

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized. Insufficient permissions.',
                     'error_code' => 'FORBIDDEN'
                 ]);
    }

    /**
     * Test access allowed for cleaner user with valid token.
     */
    public function test_access_allowed_for_cleaner()
    {
        $user = User::factory()->create(['role' => 'cleaner']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/cleaner/dashboard');

        $response->assertStatus(200);
    }
}

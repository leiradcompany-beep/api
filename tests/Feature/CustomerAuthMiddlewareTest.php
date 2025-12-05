<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class CustomerAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test blocked access without token.
     */
    public function test_access_blocked_without_token()
    {
        $response = $this->getJson('/api/customer/dashboard');

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
        ])->getJson('/api/customer/dashboard');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthenticated. Valid Bearer token required.',
                     'error_code' => 'AUTH_REQUIRED'
                 ]);
    }

    /**
     * Test access blocked for non-customer user (insufficient permissions).
     */
    public function test_access_blocked_for_non_customer()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/customer/dashboard');

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized. Insufficient permissions.',
                     'error_code' => 'FORBIDDEN'
                 ]);
    }

    /**
     * Test access allowed for customer user with valid token.
     */
    public function test_access_allowed_for_customer()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/customer/dashboard');

        $response->assertStatus(200);
    }
}

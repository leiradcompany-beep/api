<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AdminAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test blocked access without token.
     */
    public function test_access_blocked_without_token()
    {
        $response = $this->getJson('/api/admin/dashboard');

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
        ])->getJson('/api/admin/dashboard');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthenticated. Valid Bearer token required.',
                     'error_code' => 'AUTH_REQUIRED'
                 ]);
    }

    /**
     * Test access blocked for non-admin user (insufficient permissions).
     */
    public function test_access_blocked_for_non_admin()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/admin/dashboard');

        // Note: Since we use our custom middleware which checks role AFTER auth,
        // Sanctum::actingAs works by setting the user for the request.
        // Our middleware checks Auth::guard('sanctum')->user().
        
        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized. Insufficient permissions.',
                     'error_code' => 'FORBIDDEN'
                 ]);
    }

    /**
     * Test access allowed for admin user with valid token.
     */
    public function test_access_allowed_for_admin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
    }
}

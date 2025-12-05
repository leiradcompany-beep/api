<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CleanerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class CustomerFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $token;

    public function setUp(): void
    {
        parent::setUp();
        // Setup
        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@test.com',
            'email_verified_at' => now()
        ]);
        $this->token = $this->customer->createToken('auth_token')->plainTextToken;
    }

    public function test_customer_can_view_dashboard()
    {
        $response = $this->withToken($this->token)->getJson('/api/customer/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['services', 'cleaners']]);
    }

    public function test_customer_can_update_profile()
    {
        $response = $this->withToken($this->token)->postJson('/api/settings/profile', [
            'firstName' => 'Updated',
            'lastName' => 'Customer',
            'email' => 'customer@test.com', // Must provide current email if not changing or validation fails?
            'phone' => '9999999999'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'name' => 'Updated Customer',
            'phone' => '9999999999'
        ]);
    }

    public function test_customer_can_change_password()
    {
        $response = $this->withToken($this->token)->postJson('/api/settings/password', [
            'currentPassword' => 'password', // camelCase as per controller validation
            'newPassword' => 'newpassword123',
            // 'password_confirmation' => 'newpassword123' // Controller doesn't validate this currently based on read
        ]);

        $response->assertStatus(200);
        
        $this->customer->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->customer->password));
    }

    // NOTE: Booking creation currently relies on Admin route in previous tests.
    // If Customer Booking Route exists, test it here.
    // Assuming we stick to verified routes:
    public function test_customer_cannot_access_admin_routes()
    {
        $response = $this->withToken($this->token)->getJson('/api/admin/dashboard');
        $response->assertStatus(403);
    }
}

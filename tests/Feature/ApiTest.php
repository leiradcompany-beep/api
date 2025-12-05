<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Service;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'customer',
        ]);

        // After recent changes, register returns 'success' message, not token immediately (OTP required)
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create(['password' => Hash::make('password'), 'email_verified_at' => now()]); // Ensure verified

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_service()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/services', [
            'title' => 'New Service',
            'category' => 'standard',
            'price' => 100,
            'duration' => '2 hours',
            'description' => 'Test Description',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('services', ['title' => 'New Service']);
    }
}

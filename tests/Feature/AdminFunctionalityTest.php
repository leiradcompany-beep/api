<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CleanerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'email_verified_at' => now()
        ]);
        $this->token = $this->admin->createToken('auth_token')->plainTextToken;
    }

    public function test_admin_dashboard_access()
    {
        $response = $this->withToken($this->token)->getJson('/api/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_manage_services_crud()
    {
        // Create
        $res = $this->withToken($this->token)->postJson('/api/admin/services', [
            'title' => 'New Service',
            'price' => 50,
            'duration' => '1h',
            'category' => 'General'
        ]);
        $res->assertStatus(201);
        $id = $res->json('data.id');

        // Read
        $this->withToken($this->token)->getJson("/api/admin/services/{$id}")->assertStatus(200);

        // Update
        $this->withToken($this->token)->putJson("/api/admin/services/{$id}", ['price' => 60])->assertStatus(200);

        // Delete
        $this->withToken($this->token)->deleteJson("/api/admin/services/{$id}")->assertStatus(200);
    }

    public function test_admin_manage_users_crud()
    {
        // Create Customer
        $res = $this->withToken($this->token)->postJson('/api/admin/users', [
            'name' => 'Test User',
            'email' => 'u@t.com',
            'password' => 'password',
            'role' => 'customer'
        ]);
        $res->assertStatus(201);
        $uid = $res->json('data.id');

        // Delete User
        $this->withToken($this->token)->deleteJson("/api/admin/users/{$uid}")->assertStatus(200);
    }

    public function test_admin_manage_cleaners_approval()
    {
        // Create Pending Cleaner
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $profile = CleanerProfile::create(['user_id' => $cleaner->id, 'is_approved' => false]);

        // Admin Approves
        $response = $this->withToken($this->token)->putJson("/api/admin/cleaners/{$profile->id}", [
            'is_approved' => true
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('cleaner_profiles', ['id' => $profile->id, 'is_approved' => 1]);
    }

    public function test_admin_manage_bookings_assignment()
    {
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create(['title' => 'Svc', 'price' => 10, 'duration' => '1h', 'category' => 'C']);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '12:00',
            'price' => 10,
            'duration' => '1h',
            'address' => 'Test Address 123' // Required
        ]);

        // Assign Cleaner
        $response = $this->withToken($this->token)->putJson("/api/admin/bookings/{$booking->id}", [
            'cleaner_id' => $cleaner->id,
            'status' => 'confirmed'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'cleaner_id' => $cleaner->id]);
    }
}

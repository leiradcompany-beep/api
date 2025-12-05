<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackendCrudTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    /** @test */
    public function admin_can_fetch_services()
    {
        $admin = User::where('role', 'admin')->first();
        
        $response = $this->actingAs($admin)->getJson('/api/admin/services');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function admin_can_create_service()
    {
        $admin = User::where('role', 'admin')->first();

        $payload = [
            'title' => 'New Test Service',
            'category' => 'Test Category',
            'price' => 99.99,
            'duration' => '1h',
            'description' => 'Test Description'
        ];

        $response = $this->actingAs($admin)->postJson('/api/admin/services', $payload);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('services', ['title' => 'New Test Service']);
    }

    /** @test */
    public function admin_can_update_service()
    {
        $admin = User::where('role', 'admin')->first();
        $service = Service::first();

        $payload = ['title' => 'Updated Title'];

        $response = $this->actingAs($admin)->putJson("/api/admin/services/{$service->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'title' => 'Updated Title']);
    }

    /** @test */
    public function admin_can_delete_service()
    {
        $admin = User::where('role', 'admin')->first();
        $service = Service::create([
            'title' => 'Delete Me',
            'category' => 'Temp',
            'price' => 10,
            'duration' => '1h'
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/services/{$service->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    /** @test */
    public function admin_can_delete_cleaner()
    {
        $admin = User::where('role', 'admin')->first();
        $cleaner = \App\Models\CleanerProfile::first();
        $userId = $cleaner->user_id;

        $response = $this->actingAs($admin)->deleteJson("/api/admin/cleaners/{$cleaner->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cleaner_profiles', ['id' => $cleaner->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /** @test */
    public function admin_can_delete_booking()
    {
        $admin = User::where('role', 'admin')->first();
        $booking = Booking::first();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/bookings/{$booking->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function customer_can_view_dashboard()
    {
        $customer = User::where('role', 'customer')->first();

        $response = $this->actingAs($customer)->getJson('/api/customer/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['services', 'cleaners', 'bookings', 'user']
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/admin/services');
        $response->assertStatus(401);
    }
}

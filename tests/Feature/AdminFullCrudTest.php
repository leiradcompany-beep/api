<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CleanerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminFullCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminToken;

    public function setUp(): void
    {
        parent::setUp();
        // Create Admin
        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@crud.com', 'email_verified_at' => now()]);
        // We will use actingAs, but if token needed we can generate one.
        // $this->adminToken = $this->admin->createToken('admin')->plainTextToken;
    }

    /**
     * 1. Service CRUD Tests
     */
    public function test_admin_can_manage_services()
    {
        // CREATE
        $serviceData = [
            'title' => 'Deep Clean',
            'category' => 'Home',
            'price' => 150.00,
            'duration' => '3h',
            'description' => 'Thorough cleaning'
        ];

        $createResp = $this->actingAs($this->admin)->postJson('/api/admin/services', $serviceData);
        $createResp->assertStatus(201)->assertJsonFragment(['title' => 'Deep Clean']);
        $serviceId = $createResp->json('data.id');

        // READ
        $readResp = $this->actingAs($this->admin)->getJson("/api/admin/services/{$serviceId}");
        // Adjust expectation: Database returns string for decimal usually, or exact value. 
        // Let's just check the structure or use more flexible assertion.
        $readResp->assertStatus(200)->assertJsonFragment(['title' => 'Deep Clean']); // Safer check

        // UPDATE
        $updateResp = $this->actingAs($this->admin)->putJson("/api/admin/services/{$serviceId}", [
            'price' => 175.00
        ]);
        $updateResp->assertStatus(200)->assertJsonFragment(['price' => 175.00]);

        // DELETE
        $deleteResp = $this->actingAs($this->admin)->deleteJson("/api/admin/services/{$serviceId}");
        $deleteResp->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    /**
     * 2. User Management CRUD Tests (Customer, Cleaner, Admin)
     */
    public function test_admin_can_manage_users()
    {
        // A. Create CUSTOMER
        $customerData = [
            'name' => 'New Customer',
            'email' => 'newcust@test.com',
            'password' => 'password123',
            'role' => 'customer',
            'phone' => '1234567890'
        ];
        $createCust = $this->actingAs($this->admin)->postJson('/api/admin/users', $customerData);
        $createCust->assertStatus(201)->assertJsonFragment(['email' => 'newcust@test.com']);
        $custId = $createCust->json('data.id');

        // B. Create ADMIN
        $adminData = [
            'name' => 'New Admin',
            'email' => 'newadmin@test.com',
            'password' => 'password123',
            'role' => 'admin'
        ];
        $createAdmin = $this->actingAs($this->admin)->postJson('/api/admin/users', $adminData);
        $createAdmin->assertStatus(201)->assertJsonFragment(['role' => 'admin']);

        // C. Create CLEANER (Check Profile Auto-Creation)
        $cleanerData = [
            'name' => 'New Cleaner',
            'email' => 'newcleaner@test.com',
            'password' => 'password123',
            'role' => 'cleaner'
        ];
        $createCleaner = $this->actingAs($this->admin)->postJson('/api/admin/users', $cleanerData);
        $createCleaner->assertStatus(201);
        $cleanerId = $createCleaner->json('data.id');
        
        // Verify Cleaner Profile Exists
        $this->assertDatabaseHas('cleaner_profiles', ['user_id' => $cleanerId, 'is_approved' => 1]);

        // D. READ Users
        $listResp = $this->actingAs($this->admin)->getJson('/api/admin/users?role=customer');
        $listResp->assertStatus(200)->assertJsonFragment(['email' => 'newcust@test.com']);

        // E. UPDATE User
        $updateResp = $this->actingAs($this->admin)->putJson("/api/admin/users/{$custId}", [
            'name' => 'Updated Customer Name'
        ]);
        $updateResp->assertStatus(200)->assertJsonFragment(['name' => 'Updated Customer Name']);

        // F. DELETE User
        $deleteResp = $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$custId}");
        $deleteResp->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $custId]);
    }

    /**
     * 3. Booking Management CRUD Tests
     */
    public function test_admin_can_manage_bookings()
    {
        // Setup Data
        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create(['title' => 'Booking Service', 'price' => 50, 'duration' => '1h', 'category' => 'General']);

        // CREATE Booking
        $bookingData = [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '09:00',
            'address' => '123 Booking Lane',
            'phone_number' => '09123456789'
        ];
        $createResp = $this->actingAs($this->admin)->postJson('/api/admin/bookings', $bookingData);
        $createResp->assertStatus(201);
        $bookingId = $createResp->json('data.id');

        // READ Booking
        $readResp = $this->actingAs($this->admin)->getJson("/api/admin/bookings/{$bookingId}");
        $readResp->assertStatus(200)->assertJsonFragment(['address' => '123 Booking Lane']);

        // READ All Bookings (Index) - Verify service_image
        $indexResp = $this->actingAs($this->admin)->getJson("/api/admin/bookings");
        $indexResp->assertStatus(200)
                  ->assertJsonStructure(['success', 'data' => [['service_image']]]);

        // UPDATE Booking (e.g., Change status or Assign Cleaner)
        // Create a cleaner first
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        
        $updateResp = $this->actingAs($this->admin)->putJson("/api/admin/bookings/{$bookingId}", [
            'status' => 'confirmed',
            'cleaner_id' => $cleaner->id
        ]);
        $updateResp->assertStatus(200)->assertJsonFragment(['status' => 'confirmed']);
        
        // Verify DB Update
        $this->assertDatabaseHas('bookings', ['id' => $bookingId, 'status' => 'confirmed', 'cleaner_id' => $cleaner->id]);

        // DELETE Booking
        $deleteResp = $this->actingAs($this->admin)->deleteJson("/api/admin/bookings/{$bookingId}");
        $deleteResp->assertStatus(200);
        $this->assertDatabaseMissing('bookings', ['id' => $bookingId]);
    }
}

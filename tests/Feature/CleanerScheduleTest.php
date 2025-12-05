<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Service;
use App\Models\CleanerProfile;

class CleanerScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaner_schedule_filtering()
    {
        // 1. Setup: Create 2 Cleaners
        $cleaner1 = User::factory()->create(['role' => 'cleaner', 'name' => 'Cleaner One']);
        CleanerProfile::create(['user_id' => $cleaner1->id, 'is_approved' => true]);

        $cleaner2 = User::factory()->create(['role' => 'cleaner', 'name' => 'Cleaner Two']);
        CleanerProfile::create(['user_id' => $cleaner2->id, 'is_approved' => true]);

        // 2. Create Service & Customer
        $service = Service::create([
            'title' => 'Test Service', 
            'price' => 100, 
            'duration' => '1h',
            'category' => 'Massage' // Added required field
        ]);
        $customer = User::factory()->create(['role' => 'customer']);

        // 3. Create Bookings
        // Booking for Cleaner 1
        $booking1 = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner1->id,
            'date' => now()->format('Y-m-d'),
            'time' => '10:00',
            'address' => '123 Main St',
            'phone_number' => '1234567890',
            'status' => 'confirmed',
            'price' => 100,
            'duration' => '1h'
        ]);

        // Booking for Cleaner 2
        $booking2 = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner2->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '14:00',
            'address' => '456 Oak Ave',
            'phone_number' => '0987654321',
            'status' => 'pending',
            'price' => 100,
            'duration' => '1h'
        ]);

        // 4. Test API: Fetch Schedule for Cleaner 1
        // Simulate Admin Request
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)
                         ->getJson("/api/admin/bookings?cleaner_id={$cleaner1->id}");

        // 5. Verify Response
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $data = $response->json('data');

        // Assertion A: Should contain exactly 1 booking
        $this->assertCount(1, $data, 'Response should contain exactly one booking for Cleaner 1');

        // Assertion B: The booking should be Booking 1 (ID match)
        $this->assertEquals($booking1->id, $data[0]['id'], 'The returned booking ID matches Booking 1');

        // Assertion C: Should NOT contain Booking 2
        $ids = array_column($data, 'id');
        $this->assertNotContains($booking2->id, $ids, 'Response should NOT contain Booking 2');

        // Assertion D: Verify structure needed for frontend
        $this->assertArrayHasKey('service_name', $data[0]);
        $this->assertArrayHasKey('client', $data[0]);
        $this->assertArrayHasKey('status', $data[0]);
    }
}

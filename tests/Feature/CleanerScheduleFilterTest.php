<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Service;
use App\Models\CleanerProfile;

class CleanerScheduleFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_specific_cleaner_schedule()
    {
        // 1. Setup: Admin, 2 Cleaners, Customer, Service
        $admin = User::factory()->create(['role' => 'admin']);
        
        $cleaner1 = User::factory()->create(['role' => 'cleaner', 'name' => 'Cleaner One']);
        CleanerProfile::create(['user_id' => $cleaner1->id, 'is_approved' => true]);

        $cleaner2 = User::factory()->create(['role' => 'cleaner', 'name' => 'Cleaner Two']);
        CleanerProfile::create(['user_id' => $cleaner2->id, 'is_approved' => true]);

        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create([
            'title' => 'Standard Clean',
            'price' => 50,
            'duration' => '2h',
            'category' => 'Residential'
        ]);

        // 2. Create Bookings
        // Booking for Cleaner 1
        $booking1 = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner1->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '10:00',
            'address' => 'Address 1',
            'phone_number' => '123456',
            'status' => 'confirmed',
            'price' => 50,
            'duration' => '2h'
        ]);

        // Booking for Cleaner 2
        $booking2 = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner2->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '14:00',
            'address' => 'Address 2',
            'phone_number' => '654321',
            'status' => 'confirmed',
            'price' => 50,
            'duration' => '2h'
        ]);

        // 3. Act: Fetch schedule for Cleaner 1
        $response = $this->actingAs($admin)
            ->getJson("/api/admin/bookings?cleaner_id={$cleaner1->id}");

        // 4. Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data'); // Should only have 1 booking
        $response->assertJsonFragment(['id' => $booking1->id]);
        $response->assertJsonMissing(['id' => $booking2->id]);
    }

    public function test_admin_can_assign_cleaner_when_creating_booking()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        CleanerProfile::create(['user_id' => $cleaner->id, 'is_approved' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create([
            'title' => 'Deep Clean',
            'price' => 100,
            'duration' => '3h',
            'category' => 'Residential'
        ]);

        $bookingData = [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner->id, // Assigning cleaner
            'date' => now()->addDays(2)->format('Y-m-d'),
            'time' => '09:00',
            'address' => '123 Test St',
            'phone_number' => '555-5555'
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/bookings', $bookingData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', [
            'cleaner_id' => $cleaner->id,
            'user_id' => $customer->id
        ]);
    }
}

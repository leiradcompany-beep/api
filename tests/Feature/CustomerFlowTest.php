<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CleanerProfile;

class CustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_dashboard()
    {
        $user = User::factory()->create(['role' => 'customer']);
        
        // Create some data
        $service = Service::create([
            'title' => 'Basic Clean',
            'description' => 'Basic cleaning service',
            'price' => 100,
            'duration' => '60 min',
            'category' => 'residential'
        ]);

        $cleanerUser = User::factory()->create(['role' => 'cleaner']);
        $cleanerProfile = CleanerProfile::create([
            'user_id' => $cleanerUser->id,
            'job_title' => 'Cleaner',
            'rating' => 4.5
        ]);

        Booking::create([
            'user_id' => $user->id,
            'cleaner_id' => $cleanerUser->id,
            'service_id' => $service->id,
            'date' => now()->addDay(),
            'time' => '10:00:00',
            'duration' => '2 hours',
            'status' => 'confirmed',
            'address' => '123 Test St',
            'price' => 100
        ]);

        $response = $this->actingAs($user)->getJson('/api/customer/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'services',
                    'cleaners',
                    'bookings',
                    'user',
                    'upcomingJob'
                ]
            ]);
    }
}

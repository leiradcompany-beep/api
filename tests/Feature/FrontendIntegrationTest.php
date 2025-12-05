<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\CleanerProfile;
use App\Models\Service;
use App\Models\Booking;
use Carbon\Carbon;

class FrontendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_services()
    {
        Service::create([
            'title' => 'Test Service',
            'category' => 'Standard',
            'price' => 100,
            'duration' => '1h',
            'description' => 'Test desc',
            'image' => 'test.jpg'
        ]);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['title', 'category', 'price', 'image', 'description']
                ]
            ]);
    }

    public function test_customer_dashboard_data()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        $response = $this->getJson('/api/customer/dashboard');

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

    public function test_admin_dashboard_data()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        
        // Seed some data
        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create(['title'=>'S1', 'category'=>'C1', 'price'=>10, 'duration'=>'1h']);
        
        Booking::create([
             'user_id' => $customer->id,
             'service_id' => $service->id,
             'date' => Carbon::today(),
             'time' => '10:00',
             'duration' => '2 hours',
             'status' => 'confirmed',
             'price' => 100,
             'address' => '123 St'
        ]);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'stats' => ['bookings_today', 'revenue_total'],
                    'bookings',
                    'notifications'
                ]
            ]);
    }

    public function test_cleaner_dashboard_data()
    {
        $user = User::factory()->create(['role' => 'cleaner']);
        CleanerProfile::create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->getJson('/api/cleaner/dashboard');

        $response->assertStatus(200)
             ->assertJsonStructure([
                'success',
                'data' => [
                    'stats',
                    'next_job',
                    'jobs',
                    'profile'
                ]
            ]);
    }
}

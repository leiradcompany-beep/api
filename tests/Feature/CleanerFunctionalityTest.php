<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Service;
use App\Models\CleanerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CleanerFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected $cleaner;
    protected $token;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->cleaner = User::factory()->create([
            'role' => 'cleaner',
            'email' => 'cleaner@test.com',
            'email_verified_at' => now()
        ]);
        
        CleanerProfile::create([
            'user_id' => $this->cleaner->id,
            'is_approved' => true,
            'job_title' => 'Novice Cleaner'
        ]);

        $this->token = $this->cleaner->createToken('auth_token')->plainTextToken;
    }

    public function test_cleaner_can_view_dashboard()
    {
        $response = $this->withToken($this->token)->getJson('/api/cleaner/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['stats', 'next_job', 'jobs']]); // Adjusted structure
    }

    public function test_cleaner_can_update_profile_picture()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        // Must provide required fields for Profile Update as per Controller
        $response = $this->withToken($this->token)->postJson('/api/settings/profile', [
            'firstName' => 'Cleaner',
            'lastName' => 'User',
            'email' => 'cleaner@test.com',
            'avatar' => $file
        ]);

        $response->assertStatus(200);
        
        $this->cleaner->refresh();
        $this->assertNotNull($this->cleaner->avatar);
        // Verify Sync to Cleaner Profile
        $this->assertEquals($this->cleaner->avatar, $this->cleaner->cleanerProfile->img);
    }

    public function test_cleaner_cannot_access_admin_dashboard()
    {
        $response = $this->withToken($this->token)->getJson('/api/admin/dashboard');
        $response->assertStatus(403);
    }
    
    public function test_cleaner_sees_assigned_jobs()
    {
        // Create a job assigned to this cleaner
        $service = Service::create(['title' => 'Job', 'price' => 100, 'duration' => '1h', 'category' => 'Test']);
        $customer = User::factory()->create(['role' => 'customer']);
        
        Booking::create([
            'user_id' => $customer->id,
            'cleaner_id' => $this->cleaner->id,
            'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'status' => 'confirmed',
            'price' => 100,
            'duration' => '1h', // Required
            'address' => 'Job Site'
        ]);

        $response = $this->withToken($this->token)->getJson('/api/cleaner/dashboard');
        $response->assertStatus(200)
                 ->assertJsonFragment(['address' => 'Job Site']);
    }
}

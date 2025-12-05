<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CleanerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class SystemWorkflowTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * ADMIN WORKFLOW: User Management
     * - Create User (Customer/Admin/Cleaner)
     * - Read Users (Filter by role)
     * - Update User
     * - Delete User
     */
    public function test_admin_can_manage_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('admin-token')->plainTextToken;

        // 1. Create User (Customer)
        $userData = [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'password' => 'password',
            'role' => 'customer',
            'phone' => '1234567890'
        ];

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->postJson('/api/admin/users', $userData);
        
        $createResponse->assertStatus(201)
                       ->assertJsonPath('data.email', 'newcustomer@example.com');
        
        $userId = $createResponse->json('data.id');

        // 2. Read Users (Filter)
        $listResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                             ->getJson('/api/admin/users?role=customer');
        
        $listResponse->assertStatus(200)
                     ->assertJsonFragment(['email' => 'newcustomer@example.com']);

        // 3. Update User
        $updateData = ['name' => 'Updated Customer Name'];
        $updateResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->putJson("/api/admin/users/{$userId}", $updateData);
        
        $updateResponse->assertStatus(200)
                       ->assertJsonPath('data.name', 'Updated Customer Name');

        // 4. Delete User
        $deleteResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->deleteJson("/api/admin/users/{$userId}");
        
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /**
     * ADMIN WORKFLOW: Cleaner Management
     * - Create Cleaner (via CleanerController, which handles Profile)
     * - Read Cleaners
     * - Update Cleaner Profile
     * - Delete Cleaner
     */
    public function test_admin_can_manage_cleaners()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('admin-token')->plainTextToken;

        // 1. Create Cleaner
        $cleanerData = [
            'name' => 'Expert Cleaner',
            'email' => 'cleaner@example.com',
            'password' => 'password',
            'role' => 'Senior Cleaner',
            'skills' => ['Deep Cleaning', 'Sanitization'],
            'experience_years' => 5
        ];

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->postJson('/api/admin/cleaners', $cleanerData);

        $createResponse->assertStatus(201)
                       ->assertJsonPath('data.job_title', 'Senior Cleaner'); // Note: Check response structure if 'user_title' or 'job_title' is returned directly or nested

        // Re-verify the response structure from CleanerController::store
        // It returns CleanerProfile model.
        $cleanerProfileId = $createResponse->json('data.id');
        
        // 2. Read Cleaners
        $listResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                             ->getJson('/api/admin/cleaners');

        $listResponse->assertStatus(200)
                     ->assertJsonFragment(['name' => 'Expert Cleaner']);

        // 3. Update Cleaner
        $updateData = [
            'name' => 'Expert Cleaner Updated',
            'experience_years' => 6
        ];
        
        $updateResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->putJson("/api/admin/cleaners/{$cleanerProfileId}", $updateData);

        $updateResponse->assertStatus(200)
                       ->assertJsonPath('data.experience_years', 6);

        // 4. Delete Cleaner
        $deleteResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->deleteJson("/api/admin/cleaners/{$cleanerProfileId}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('cleaner_profiles', ['id' => $cleanerProfileId]);
    }

    /**
     * ADMIN WORKFLOW: Booking Management
     * - Create Booking (Admin creates for user)
     * - Assign Cleaner (Update Booking)
     * - Check Status
     */
    public function test_admin_booking_lifecycle()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        // Ensure cleaner has profile
        CleanerProfile::factory()->create(['user_id' => $cleaner->id]);

        $service = Service::factory()->create(['price' => 100, 'duration' => '2h']);
        $token = $admin->createToken('admin-token')->plainTextToken;

        // 1. Create Booking (Unassigned)
        $bookingData = [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'time' => '14:00',
            'address' => '123 Main St',
            'status' => 'pending'
        ];

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->postJson('/api/admin/bookings', $bookingData);

        $createResponse->assertStatus(201);
        $bookingId = $createResponse->json('data.id');

        // 2. Assign Cleaner & Update Status
        $updateData = [
            'cleaner_id' => $cleaner->id,
            'status' => 'confirmed'
        ];

        $updateResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->putJson("/api/admin/bookings/{$bookingId}", $updateData);

        $updateResponse->assertStatus(200)
                       ->assertJsonPath('data.cleaner_id', $cleaner->id)
                       ->assertJsonPath('data.status', 'confirmed');
    }

    /**
     * CUSTOMER WORKFLOW
     * - View Own Data (via Settings/Profile)
     * - Update Profile
     * - (Note: Customer booking creation via API is currently not exposed directly to 'customer' role in routes, 
     *   so we verify they can at least manage their account)
     */
    public function test_customer_profile_management()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = $customer->createToken('customer-token')->plainTextToken;

        // 1. View Profile
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/api/user');
        
        $response->assertStatus(200)
                 ->assertJsonPath('data.email', $customer->email);

        // 2. Update Profile
        $updateData = [
            'firstName' => 'Updated',
            'lastName' => 'Customer',
            'email' => $customer->email,
            'phone' => '9876543210'
        ];

        // Note: The route is /api/settings/profile based on api.php
        $updateResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->postJson('/api/settings/profile', $updateData);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'name' => 'Updated Customer']);
    }

    /**
     * CLEANER WORKFLOW
     * - View Dashboard (See assigned jobs)
     */
    public function test_cleaner_view_assignments()
    {
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        CleanerProfile::factory()->create(['user_id' => $cleaner->id]);
        $token = $cleaner->createToken('cleaner-token')->plainTextToken;

        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::factory()->create();

        // Create a booking assigned to this cleaner
        Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner->id,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '10:00:00',
            'status' => 'confirmed',
            'address' => 'Job Location',
            'price' => 50,
            'duration' => '1h'
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/api/cleaner/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
        
        // Verify job is in the list
        $jobs = $response->json('data.jobs');
        $this->assertTrue(count($jobs) > 0);
        $this->assertEquals($customer->name, $jobs[0]['customer']);
    }
}

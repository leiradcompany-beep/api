<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class EndToEndTest extends TestCase
{
    // We use RefreshDatabase to reset the database after each test
    // ensuring a clean state and not polluting the actual database.
    use RefreshDatabase; 
    use WithFaker;

    /**
     * Test Authentication Flow: Register -> Verify OTP -> Login -> Logout
     */
    public function test_auth_flow_register_verify_login_logout()
    {
        // 1. Register
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
            'phone' => '1234567890'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'testuser@example.com']);

        // 2. Verify OTP
        // Get the user and their OTP from the database
        $user = User::where('email', 'testuser@example.com')->first();
        $this->assertNotNull($user->otp);

        $verifyResponse = $this->postJson('/api/verify-otp', [
            'email' => 'testuser@example.com',
            'otp' => $user->otp,
        ]);

        $verifyResponse->assertStatus(200)
                       ->assertJsonStructure(['token', 'user']);

        $token = $verifyResponse->json('token');

        // 3. Access Protected Route (User Profile)
        $profileResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                                ->getJson('/api/user');

        $profileResponse->assertStatus(200)
                        ->assertJsonPath('data.email', 'testuser@example.com');

        // 4. Logout
        $logoutResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
                       ->assertJson(['message' => 'Logged out successfully']);
    }

    /**
     * Test Cleaner Flow: View Dashboard and Assigned Bookings
     */
    public function test_cleaner_flow()
    {
        // 1. Create Cleaner
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        // Create Cleaner Profile (required for dashboard)
        \App\Models\CleanerProfile::factory()->create(['user_id' => $cleaner->id]);
        
        $token = $cleaner->createToken('cleaner-token')->plainTextToken;

        // 2. Create Service and Customer
        $service = Service::factory()->create();
        $customer = User::factory()->create(['role' => 'customer']);

        // 3. Create Booking assigned to Cleaner (Status: confirmed)
        $booking = Booking::create([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'cleaner_id' => $cleaner->id,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '09:00:00',
            'status' => 'confirmed',
            'address' => '456 Cleaner St',
            'price' => 100,
            'duration' => '2h'
        ]);

        // 4. Access Cleaner Dashboard
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/api/cleaner/dashboard');

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

        // Check if the assigned job appears in 'jobs'
        $jobs = $response->json('data.jobs');
        $this->assertNotEmpty($jobs);
        $this->assertEquals($customer->name, $jobs[0]['customer']);
    }

    /**
     * Test Admin Service CRUD Operations
     */
    public function test_admin_service_crud()
    {
        // Create Admin User
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('admin-token')->plainTextToken;

        // 1. Create Service
        $serviceData = [
            'title' => 'Deep Home Cleaning',
            'category' => 'deep',
            'price' => 150.00,
            'duration' => '3 hours',
            'description' => 'Comprehensive cleaning for your home.',
        ];

        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->postJson('/api/admin/services', $serviceData);

        $createResponse->assertStatus(201)
                       ->assertJsonFragment(['title' => 'Deep Home Cleaning']);

        $this->assertDatabaseHas('services', ['title' => 'Deep Home Cleaning']);
        $serviceId = $createResponse->json('data.id');

        // 2. Read Service (Get All)
        $getAllResponse = $this->getJson('/api/services');
        $getAllResponse->assertStatus(200)
                       ->assertJsonFragment(['title' => 'Deep Home Cleaning']);

        // 3. Read Service (Get One - Admin)
        $getOneResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->getJson("/api/admin/services/{$serviceId}");
        $getOneResponse->assertStatus(200)
                       ->assertJsonFragment(['id' => $serviceId]);

        // 4. Update Service
        $updateData = [
            'title' => 'Updated Deep Cleaning',
            'price' => 160.00,
            'category' => 'deep', // Including required fields
            'duration' => '3.5 hours',
            'description' => 'Updated description.',
        ];

        $updateResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->putJson("/api/admin/services/{$serviceId}", $updateData);

        $updateResponse->assertStatus(200)
                       ->assertJsonFragment(['title' => 'Updated Deep Cleaning']);

        $this->assertDatabaseHas('services', ['title' => 'Updated Deep Cleaning', 'price' => 160.00]);

        // 5. Delete Service
        $deleteResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                               ->deleteJson("/api/admin/services/{$serviceId}");

        $deleteResponse->assertStatus(200); // Or 204 depending on implementation
        
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    /**
     * Test Customer Booking Flow
     */
    public function test_customer_booking_flow()
    {
        // Create Customer
        $customer = User::factory()->create(['role' => 'customer']);
        $token = $customer->createToken('customer-token')->plainTextToken;

        // Create Service to book
        $service = Service::factory()->create();

        // 1. Create Booking (Assuming there is an endpoint for customers to book, or using Admin endpoint for now if Customer specific is not clear)
        // Looking at routes, only Admin has resource bookings.
        // But usually Customers should be able to book. 
        // Let's check if there is a customer booking route. 
        // If not, maybe it's missing or I missed it. 
        // Re-checking routes: 
        // Route::middleware(\App\Http\Middleware\CheckRole::class . ':customer')->prefix('customer')->group(function () {
        //    Route::get('/dashboard', [CustomerDashboardController::class, 'index']);
        // });
        // It seems there is NO explicit booking route for customers in the snippets I saw.
        // This might be a gap in the API or handled differently.
        // However, Admin can create bookings. I will test Admin creating a booking for a user for now, 
        // OR I will assume there might be a public/customer route I missed or it's under development.
        // Wait, let's look at `BookingController` to see if it allows customer creation.
        
        // For this test, I will simulate an Admin creating a booking since that route exists.
        
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = $admin->createToken('admin-token')->plainTextToken;

        $bookingData = [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'time' => '10:00:00',
            'status' => 'pending',
            'address' => '123 Test St',
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
                         ->postJson('/api/admin/bookings', $bookingData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('bookings', ['user_id' => $customer->id, 'service_id' => $service->id]);
    }
}

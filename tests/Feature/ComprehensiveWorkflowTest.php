<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CleanerProfile;
use App\Mail\OtpMail;

class ComprehensiveWorkflowTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('documents');
    }

    /**
     * Authentication Testing: Customer Registration, OTP Verification, and Login
     */
    public function test_customer_auth_workflow()
    {
        Mail::fake();

        // 1. Register
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->otp);

        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // 2. Login before verification (should fail or require verification)
        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(403); // "Email not verified"

        // 3. Verify OTP
        $response = $this->postJson('/api/verify-otp', [
            'email' => 'john@example.com',
            'otp' => $user->otp,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // 4. Login after verification
        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /**
     * Authentication Testing: Cleaner Registration, Approval, and Login
     */
    public function test_cleaner_auth_and_approval_workflow()
    {
        Mail::fake();

        // 1. Register Cleaner
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Cleaner',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'role' => 'cleaner',
            'experience' => 5,
            'specialization' => 'Deep Clean',
            'id_document' => UploadedFile::fake()->create('id.pdf', 100),
            'background_check' => UploadedFile::fake()->create('bg.pdf', 100),
        ]);

        $response->assertStatus(201);
        $user = User::where('email', 'jane@example.com')->first();
        
        // Verify OTP manually for test speed
        $user->email_verified_at = now();
        $user->save();

        // 2. Login (Should fail because not approved)
        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(403); // Not approved

        // 3. Admin Approves Cleaner
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('admin')->plainTextToken;

        $cleanerProfile = CleanerProfile::where('user_id', $user->id)->first();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->putJson("/api/admin/cleaners/{$cleanerProfile->id}", [
                'is_approved' => true
            ]);

        $response->assertStatus(200);

        // 4. Login (Should succeed now)
        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(200);
    }

    /**
     * Role-Based Functionality: Admin CRUD on Services
     */
    public function test_admin_service_crud()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('admin')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create
        $response = $this->withHeaders($headers)->postJson('/api/admin/services', [
            'title' => 'Test Service',
            'category' => 'residential',
            'price' => 150,
            'duration' => '3 hours',
            'description' => 'A test service',
        ]);
        $response->assertStatus(201);
        $serviceId = $response->json('id'); // Assuming response returns created object or we find it

        // If response doesn't return ID directly, find it
        $service = Service::where('title', 'Test Service')->first();
        $this->assertNotNull($service);
        $serviceId = $service->id;

        // Read (via Public API)
        $response = $this->getJson('/api/services');
        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Test Service']);

        // Update
        $response = $this->withHeaders($headers)->putJson("/api/admin/services/{$serviceId}", [
            'title' => 'Updated Service',
            'price' => 200,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('services', ['title' => 'Updated Service', 'price' => 200]);

        // Delete
        $response = $this->withHeaders($headers)->deleteJson("/api/admin/services/{$serviceId}");
        $response->assertStatus(200); // Or 204
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    /**
     * Role-Based Functionality: Customer Dashboard
     */
    public function test_customer_dashboard_access()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = $customer->createToken('customer')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/customer/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Role-Based Functionality: Admin Booking Management
     */
    public function test_admin_booking_management()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('admin')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $customer = User::factory()->create(['role' => 'customer']);
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $service = Service::factory()->create();

        // Create Booking (Admin)
        $bookingData = [
            'user_id' => $customer->id,
            'cleaner_id' => $cleaner->id,
            'service_id' => $service->id,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00:00',
            'duration' => '2 hours',
            'status' => 'confirmed',
            'address' => '123 Test St',
            'price' => 100
        ];

        $response = $this->withHeaders($headers)->postJson('/api/admin/bookings', $bookingData);
        
        // Note: Depending on BookingController implementation, verify status code
        // If BookingController uses `create` method and returns resource
        if ($response->status() === 405) {
             // Method not allowed, maybe store is not implemented or different route
             // But we saw `apiResource` so `store` should be there.
        }
        $response->assertStatus(201); 
        
        $booking = Booking::where('user_id', $customer->id)->first();
        $this->assertNotNull($booking);

        // Update Booking
        $response = $this->withHeaders($headers)->putJson("/api/admin/bookings/{$booking->id}", [
            'status' => 'completed'
        ]);
        $response->assertStatus(200);
        $this->assertEquals('completed', $booking->fresh()->status);

        // Delete Booking
        $response = $this->withHeaders($headers)->deleteJson("/api/admin/bookings/{$booking->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
    
    /**
     * Automation & Error Handling: Invalid Login
     */
    public function test_invalid_login_handling()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /**
     * Database Verification: Constraints (e.g., Unique Email)
     */
    public function test_duplicate_registration_fails()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'role' => 'customer'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}

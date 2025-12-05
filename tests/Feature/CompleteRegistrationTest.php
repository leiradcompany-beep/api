<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use App\Models\User;
use App\Mail\RegisterOtpMail;

class CompleteRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // We mock Mail to ensure tests don't fail due to external SMTP limits
        // and to verify that the application *attempts* to send the mail.
        Mail::fake();
    }

    /**
     * Test Case 1: "I Want to Book" (Customer) Registration
     */
    public function test_customer_registration_flow()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'customer@test.com',
            'password' => 'password123',
            'role' => 'customer',
        ];

        // 1. Submit Registration Form
        $response = $this->postJson('/api/register', $userData);

        // 2. Assert Response
        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'message', 'email', 'role']);

        // 3. Verify Database Persistence
        $this->assertDatabaseHas('users', [
            'email' => 'customer@test.com',
            'role' => 'customer',
            'email_verified_at' => null // Should be null initially
        ]);

        // 4. Verify OTP Email was "sent" (Mailable queued)
        Mail::assertSent(RegisterOtpMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });
    }

    /**
     * Test Case 2: "I Want to Work" (Cleaner) Registration
     */
    public function test_cleaner_registration_flow()
    {
        Storage::fake('public');

        $cleanerData = [
            'name' => 'Jane Cleaner',
            'email' => 'cleaner@test.com',
            'password' => 'password123',
            'role' => 'cleaner',
            'experience' => 5,
            'specialization' => 'residential',
            // Simulate file uploads
            'id_front' => UploadedFile::fake()->image('front_id.jpg'),
            'id_back' => UploadedFile::fake()->image('back_id.jpg'),
        ];

        // 1. Submit Registration Form
        $response = $this->postJson('/api/register', $cleanerData);

        // 2. Assert Response
        $response->assertStatus(201);

        // 3. Verify User Database Persistence
        $this->assertDatabaseHas('users', [
            'email' => 'cleaner@test.com',
            'role' => 'cleaner'
        ]);

        // 4. Verify Cleaner Profile Persistence
        $user = User::where('email', 'cleaner@test.com')->first();
        $this->assertDatabaseHas('cleaner_profiles', [
            'user_id' => $user->id,
            'experience_years' => 5,
            'specialization' => 'residential',
            'is_approved' => false
        ]);

        // 5. Verify File Storage
        // The controller stores in uploads/cleaners/{id}/ids
        // We need to check if files exist in that directory
        // Note: Storage::fake uses a temporary directory, Laravel hash-names files.
        // We check if the path in DB exists.
        $profile = $user->cleanerProfile;
        $this->assertNotNull($profile->id_document_path);
        $this->assertNotNull($profile->background_check_path);
        
        // Remove '/storage/' prefix to check existence in disk
        $storagePathFront = str_replace('/storage/', '', $profile->id_document_path);
        $storagePathBack = str_replace('/storage/', '', $profile->background_check_path);

        Storage::disk('public')->assertExists($storagePathFront);
        Storage::disk('public')->assertExists($storagePathBack);
    }

    /**
     * Test Case 3: OTP Verification Process
     */
    public function test_otp_verification_process()
    {
        // Setup: Create a user with a known OTP
        $otp = '123456';
        $user = User::factory()->create([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'email_verified_at' => null
        ]);

        // Scenario A: Invalid OTP
        $response = $this->postJson('/api/verify-otp', [
            'email' => $user->email,
            'otp' => '000000' // Wrong code
        ]);
        $response->assertStatus(400)
                 ->assertJson(['message' => 'Invalid OTP']);

        // Scenario B: Correct OTP
        $response = $this->postJson('/api/verify-otp', [
            'email' => $user->email,
            'otp' => $otp
        ]);
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'token', 'user']);
        
        // Verify Database Update
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->otp); // OTP should be cleared
    }

    /**
     * Test Case 4: Validation and Error Handling
     */
    public function test_registration_validation()
    {
        // 1. Missing Fields
        $response = $this->postJson('/api/register', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);

        // 2. Duplicate Email
        User::factory()->create(['email' => 'duplicate@test.com']);
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'duplicate@test.com',
            'password' => 'password123',
            'role' => 'customer'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        // 3. Invalid File Type (Cleaner)
        $response = $this->postJson('/api/register', [
            'name' => 'Cleaner Bad File',
            'email' => 'cleaner2@test.com',
            'password' => 'password123',
            'role' => 'cleaner',
            'id_front' => UploadedFile::fake()->create('document.pdf', 100), // PDF not allowed
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_front']);
    }
}

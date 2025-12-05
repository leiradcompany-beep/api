<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use App\Models\CleanerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    /** @test */
    public function admin_can_upload_image_for_service()
    {
        $admin = User::where('role', 'admin')->first();
        $file = UploadedFile::fake()->image('service.jpg');

        $payload = [
            'title' => 'Service with Image',
            'category' => 'Test',
            'price' => 50,
            'duration' => '1h',
            'image' => $file
        ];

        $response = $this->actingAs($admin)->postJson('/api/admin/services', $payload);

        $response->assertStatus(201);
        
        // Verify DB
        $service = Service::where('title', 'Service with Image')->first();
        $this->assertStringContainsString('../../assets/services/', $service->image);
        
        // Clean up the file created in the real directory
        $filename = basename($service->image);
        $path = base_path('../frontend/assets/services/' . $filename);
        if(file_exists($path)) {
            unlink($path);
        }
    }

    /** @test */
    public function admin_can_upload_image_for_cleaner()
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin')->first();
        $file = UploadedFile::fake()->image('cleaner.jpg');

        $payload = [
            'name' => 'Cleaner With Image',
            'email' => 'cleaner_img@test.com',
            'password' => 'password',
            'role' => 'Expert',
            'img' => $file
        ];

        $response = $this->actingAs($admin)->postJson('/api/admin/cleaners', $payload);

        $response->assertStatus(201);

        // Verify file stored
        $files = Storage::disk('public')->files('cleaners');
        $this->assertNotEmpty($files);

        // Verify DB
        $cleaner = CleanerProfile::where('job_title', 'Expert')->first(); // We don't have direct access to name in profile query easily without join, but checking latest created
        $cleanerUser = User::where('email', 'cleaner_img@test.com')->first();
        $cleanerProfile = CleanerProfile::where('user_id', $cleanerUser->id)->first();

        $this->assertStringContainsString('/storage/cleaners/', $cleanerProfile->img);
    }

    /** @test */
    public function image_upload_fails_validation_for_invalid_file()
    {
        $admin = User::where('role', 'admin')->first();
        $file = UploadedFile::fake()->create('document.pdf', 100); // Not an image

        $payload = [
            'title' => 'Invalid Service',
            'category' => 'Test',
            'price' => 50,
            'duration' => '1h',
            'image' => $file
        ];

        $response = $this->actingAs($admin)->postJson('/api/admin/services', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }
}

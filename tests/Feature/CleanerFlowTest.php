<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\CleanerProfile;

class CleanerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaner_can_register_with_profile()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Cleaner Test',
            'email' => 'cleaner@test.com',
            'password' => 'password123',
            'role' => 'cleaner',
            'experience' => 5,
            'specialization' => 'deep_cleaning',
            'skills' => 'Ironing, Windows, Eco-friendly',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'cleaner@test.com']);
        
        $user = User::where('email', 'cleaner@test.com')->first();
        $profile = CleanerProfile::where('user_id', $user->id)->first();

        $this->assertNotNull($profile);
        $this->assertEquals(5, $profile->experience_years);
        $this->assertEquals('deep_cleaning', $profile->specialization);
        
        // Check if skills are stored as array (casted)
        // Note: assertDatabaseHas checks the raw DB value which is JSON string for array columns in MySQL/Postgres
        // but when accessed via Eloquent it is an array.
        
        // We can check the Eloquent model property
        $this->assertTrue(is_array($profile->skills));
        $this->assertContains('Ironing', $profile->skills);
        $this->assertContains('Windows', $profile->skills);
        $this->assertContains('Eco-friendly', $profile->skills);
    }

    public function test_cleaner_can_access_dashboard()
    {
        $user = User::factory()->create(['role' => 'cleaner']);
        $profile = CleanerProfile::create([
            'user_id' => $user->id,
            'experience_years' => 3,
            'specialization' => 'residential',
            'job_title' => 'Residential Specialist',
            'jobs' => 10,
            'rating' => 4.8
        ]);

        $response = $this->actingAs($user)->getJson('/api/cleaner/dashboard');

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

    public function test_token_refresh()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/refresh-token');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'token']);
    }
}

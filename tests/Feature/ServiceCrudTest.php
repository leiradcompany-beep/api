<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_read_update_delete_service()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['*']);

        // 1. Create
        $createResponse = $this->postJson('/api/admin/services', [
            'title' => 'Test Service',
            'category' => 'Relaxation',
            'price' => 100,
            'duration' => '1h',
            'description' => 'A test service'
        ]);
        $createResponse->assertStatus(201);
        $serviceId = $createResponse->json('data.id');

        // 2. Read
        $readResponse = $this->getJson('/api/admin/services');
        $readResponse->assertStatus(200)
                     ->assertJsonFragment(['title' => 'Test Service']);

        // 3. Update
        $updateResponse = $this->putJson("/api/admin/services/{$serviceId}", [
            'title' => 'Updated Service',
            'price' => 150
        ]);
        $updateResponse->assertStatus(200)
                       ->assertJsonFragment(['title' => 'Updated Service', 'price' => 150]);

        // 4. Delete
        $deleteResponse = $this->deleteJson("/api/admin/services/{$serviceId}");
        $deleteResponse->assertStatus(200);

        // Verify deletion
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use Laravel\Sanctum\Sanctum;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_created_on_service_creation()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/admin/services', [
            'title' => 'Test Service',
            'category' => 'Test',
            'price' => 100,
            'duration' => '1h',
            'description' => 'Test Desc'
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'model' => 'Service',
            'user_id' => $user->id
        ]);
    }
}

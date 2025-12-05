<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class DatabaseSchemaAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Database Connection & Schema Verification
     */
    public function test_database_connection_and_schema_structure()
    {
        // Verify Connection
        try {
            DB::connection()->getPdo();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("Could not connect to the database: " . $e->getMessage());
        }

        // Verify Tables Exist
        $tables = ['users', 'services', 'bookings', 'cleaner_profiles', 'settings', 'personal_access_tokens'];
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table '$table' does not exist.");
        }

        // Verify Critical Columns in Users
        $userColumns = ['id', 'name', 'email', 'password', 'role', 'otp', 'otp_expires_at'];
        foreach ($userColumns as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Column '$column' missing in users table.");
        }

        // Verify Critical Columns in Bookings
        $bookingColumns = ['id', 'user_id', 'cleaner_id', 'service_id', 'status', 'price', 'date', 'time'];
        foreach ($bookingColumns as $column) {
            $this->assertTrue(Schema::hasColumn('bookings', $column), "Column '$column' missing in bookings table.");
        }
    }

    /**
     * 4. Transaction Integrity Test
     */
    public function test_database_transaction_integrity_rollback()
    {
        $countBefore = User::count();

        try {
            DB::transaction(function () {
                User::create([
                    'name' => 'Rollback User',
                    'email' => 'rollback@test.com',
                    'password' => 'password',
                    'role' => 'customer'
                ]);

                // Simulate Failure
                throw new \Exception("Simulated Failure");
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        $countAfter = User::count();

        // Assert that the user count did NOT increase
        $this->assertEquals($countBefore, $countAfter, "Transaction failed to rollback data.");
    }

    /**
     * 5. Performance Benchmark: Bulk Operations
     */
    public function test_performance_bulk_booking_creation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create(['title' => 'Perf Service', 'price' => 10, 'duration' => '1h', 'category' => 'Perf']);

        $startTime = microtime(true);

        // Create 50 Bookings
        for ($i = 0; $i < 50; $i++) {
            $this->actingAs($admin)->postJson('/api/admin/bookings', [
                'user_id' => $customer->id,
                'service_id' => $service->id,
                'date' => '2025-12-25',
                'time' => '10:00',
                'address' => "Addr $i"
            ]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Benchmark: 50 bookings should be under 5 seconds (generous for local env)
        $this->assertLessThan(5.0, $executionTime, "Performance Warning: Bulk booking creation took too long ($executionTime s).");
    }

    /**
     * 2. Auth & Edge Case: Invalid Login
     */
    public function test_auth_negative_case_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'valid@test.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now()
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'valid@test.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid login details']);
    }

    /**
     * 3. CRUD Boundary: Unauthorized Access
     */
    public function test_boundary_unauthorized_service_deletion()
    {
        $service = Service::create(['title' => 'Protected Service', 'price' => 10, 'duration' => '1h', 'category' => 'Test']);
        $customer = User::factory()->create(['role' => 'customer']);

        // Customer tries to delete service
        $response = $this->actingAs($customer)->deleteJson("/api/admin/services/{$service->id}");

        // Should be Forbidden
        $response->assertStatus(403);
        
        // Assert it still exists
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }
}

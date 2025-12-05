<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum definition to include 'assigned' and 'declined'
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'assigned', 'confirmed', 'completed', 'cancelled', 'declined') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        // Note: Any records with 'assigned' or 'declined' will cause issues if not handled. 
        // For safety in development, we might just leave it or map them back.
        // Here we just revert the definition.
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};

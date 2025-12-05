<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cleaner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('job_title')->nullable();
            $table->string('img')->nullable();
            $table->integer('jobs')->default(0);
            $table->decimal('rating', 3, 1)->default(5.0);
            $table->json('skills')->nullable();
            $table->integer('experience_years')->nullable();
            $table->string('specialization')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('background_check_path')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaner_profiles');
    }
};

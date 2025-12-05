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
        // Truncate reviews to avoid FK errors since we are adding a non-nullable FK to existing data
        \Illuminate\Support\Facades\DB::table('reviews')->truncate();

        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'service_id')) {
                $table->foreignId('service_id')->after('cleaner_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('reviews', 'service_rating')) {
                $table->integer('service_rating')->after('rating')->default(5); 
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            }
            if (Schema::hasColumn('reviews', 'service_rating')) {
                $table->dropColumn('service_rating');
            }
        });
    }
};

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
        Schema::table('cleaner_profiles', function (Blueprint $table) {
            $table->string('valid_id_type')->nullable()->after('specialization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaner_profiles', function (Blueprint $table) {
            $table->dropColumn('valid_id_type');
        });
    }
};

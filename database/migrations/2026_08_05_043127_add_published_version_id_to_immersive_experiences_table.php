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
        Schema::table('immersive_experiences', function (Blueprint $table) {
            $table->foreignId('published_version_id')->nullable()->after('desktop_quality_profile')
                ->constrained('experience_versions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immersive_experiences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_version_id');
        });
    }
};

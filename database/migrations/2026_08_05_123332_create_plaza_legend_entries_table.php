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
        Schema::create('plaza_legend_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_plaza_id')->constrained()->cascadeOnDelete();
            $table->string('color_hex', 7);
            $table->unsignedInteger('detected_pixel_count')->default(0);
            $table->foreignId('object_template_id')->nullable()->constrained('immersive_object_templates')->nullOnDelete();
            $table->enum('status', ['pendiente', 'confirmado'])->default('pendiente');
            $table->timestamps();

            $table->unique(['immersive_plaza_id', 'color_hex']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plaza_legend_entries');
    }
};

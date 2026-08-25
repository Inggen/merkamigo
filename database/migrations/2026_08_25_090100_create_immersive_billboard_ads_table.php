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
        Schema::create('immersive_billboard_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_plaza_prop_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immersive_billboard_ads');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table): void {
            $table->string('asset_input_mode')->default('model_3d')->after('builder_key');
            $table->json('ai_draft_definition')->nullable()->after('model_definition');
            $table->json('ai_reference_images')->nullable()->after('ai_draft_definition');
            $table->json('ai_instruction_log')->nullable()->after('ai_reference_images');
        });
    }

    public function down(): void
    {
        Schema::table('immersive_object_templates', function (Blueprint $table): void {
            $table->dropColumn([
                'asset_input_mode',
                'ai_draft_definition',
                'ai_reference_images',
                'ai_instruction_log',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fila única (singleton): configuración editable de OpenAI desde el
     * admin. No toca usuarios ni vitrinas; solo guarda credenciales y
     * banderas para activar IA en superficies futuras.
     */
    public function up(): void
    {
        Schema::create('openai_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->boolean('entrepreneur_copilot_enabled')->default(false);
            $table->string('model')->nullable();
            $table->text('api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->unsignedSmallInteger('max_output_tokens')->nullable();
            $table->decimal('temperature', 3, 2)->nullable();
            $table->text('system_prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openai_settings');
    }
};

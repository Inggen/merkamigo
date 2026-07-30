<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('level')->default('basica');
            $table->enum('status', ['sin_iniciar', 'en_revision', 'requiere_ajustes', 'verificada', 'vencida', 'revocada'])
                ->default('sin_iniciar');
            $table->string('legal_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_document_type')->nullable();
            $table->string('contact_document_number')->nullable();
            $table->string('verification_document_path')->nullable();
            $table->text('request_note')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_verifications');
    }
};

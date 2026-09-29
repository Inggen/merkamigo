<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adjuntos e imagen en mensajes (un mensaje puede ir sin texto si trae
 * foto — `body` sigue NOT NULL, `SendBusinessMessage` guarda '' cuando
 * solo hay adjunto, así se evita un `MODIFY` de columna que requeriría
 * instalar `doctrine/dbal`, que este proyecto no tiene) + borrado suave
 * de conversaciones (pedido del usuario: adjuntos, revisión de FCM y
 * borrado/moderación de conversaciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('body');
        });

        Schema::table('business_conversations', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('business_conversations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('business_messages', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });
    }
};

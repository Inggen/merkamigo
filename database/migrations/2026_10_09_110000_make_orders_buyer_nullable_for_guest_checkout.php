<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2, decisión #6 escalada con
     * el usuario el 2026-10-09): checkout de invitado para productos
     * digitales, sin exigir cuenta. Dos cambios aditivos:
     *
     * - `buyer_user_id` pasa a nullable (un invitado no tiene usuario) y
     *   de `cascadeOnDelete()` a `nullOnDelete()` — antes, borrar la
     *   cuenta de un comprador borraba en cascada su historial de
     *   ventas reales; ahora el pedido sobrevive sin comprador
     *   identificado, igual que ya sobreviviría uno de invitado.
     * - `guest_name`/`guest_email`/`guest_phone`: datos de contacto del
     *   invitado (mismo patrón que `EventAttendance.attendee_*`) para
     *   poder enviarle la confirmación y el enlace de descarga sin
     *   cuenta.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['buyer_user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_user_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('buyer_user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('guest_name')->nullable()->after('buyer_user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_email', 'guest_phone']);
            $table->dropForeign(['buyer_user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_user_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('buyer_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

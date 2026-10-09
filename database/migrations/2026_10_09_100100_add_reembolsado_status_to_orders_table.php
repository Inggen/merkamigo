<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR2 de TODO_VENTAS_RENTABILIDAD.md (hallazgo #3 de la auditoría,
     * docs/auditoria-ventas-rentabilidad.md §1.3): un `VOIDED` de Wompi
     * que llega DESPUÉS de que el pedido ya estaba `pagado` (reembolso
     * real) se perdía en la misma categoría que un pago que nunca llegó
     * a aprobarse (`rechazado`) — sin poder distinguir "nunca se cobró"
     * de "se cobró y se devolvió", ni disparar la reversa de la comisión
     * ya acumulada. `->change()` reescribe el ENUM nativo en MySQL y
     * recrea la tabla en SQLite, sin depender de `doctrine/dbal` (mismo
     * patrón que `add_barrera_category_to_immersive_object_templates`).
     * Mismo nombre que ya usa `Payment::REEMBOLSADO` en Billing.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pendiente', 'pagado', 'rechazado', 'cancelado', 'reembolsado'])
                ->default('pendiente')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pendiente', 'pagado', 'rechazado', 'cancelado'])
                ->default('pendiente')
                ->change();
        });
    }
};

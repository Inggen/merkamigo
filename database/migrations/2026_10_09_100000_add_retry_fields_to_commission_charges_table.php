<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR2 de TODO_VENTAS_RENTABILIDAD.md (hallazgo #4 de la auditoría,
     * docs/auditoria-ventas-rentabilidad.md §2.2): una comisión `fallida`
     * nunca se volvía a intentar. `retry_count` cuenta los reintentos ya
     * hechos (hasta agotar el backoff de `ChargeCommission`) y
     * `next_retry_at` es cuándo el lote semanal puede volver a intentarla
     * — null significa "agotó los reintentos automáticos, requiere
     * revisión manual en el panel de conciliación".
     */
    public function up(): void
    {
        Schema::table('commission_charges', function (Blueprint $table) {
            $table->unsignedTinyInteger('retry_count')->default(0)->after('status');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('commission_charges', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'next_retry_at']);
        });
    }
};

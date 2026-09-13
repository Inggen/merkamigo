<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identificadores reales para Google Shopping (nunca inventados: solo
     * se envían si el emprendedor los llena) y estado de sincronización
     * por producto. `google_merchant_synced_hash` evita reenviar a Google
     * un producto cuyos campos relevantes no cambiaron.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('gtin')->nullable()->after('unit');
            $table->string('mpn')->nullable()->after('gtin');
            $table->string('brand')->nullable()->after('mpn');
            $table->enum('condition', ['nuevo', 'usado', 'reacondicionado'])->default('nuevo')->after('brand');

            $table->enum('google_merchant_status', [
                'no_publicado', 'pendiente', 'sincronizando', 'requiere_ajustes', 'publicado', 'error',
            ])->default('no_publicado')->after('status');
            $table->string('google_merchant_product_id')->nullable()->after('google_merchant_status');
            $table->timestamp('google_merchant_last_sync_at')->nullable()->after('google_merchant_product_id');
            $table->text('google_merchant_last_error')->nullable()->after('google_merchant_last_sync_at');
            $table->string('google_merchant_synced_hash')->nullable()->after('google_merchant_last_error');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'gtin',
                'mpn',
                'brand',
                'condition',
                'google_merchant_status',
                'google_merchant_product_id',
                'google_merchant_last_sync_at',
                'google_merchant_last_error',
                'google_merchant_synced_hash',
            ]);
        });
    }
};

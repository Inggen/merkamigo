<?php

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identidad de vendedor y estado de sincronización para Google Merchant
     * Center (integración marketplace/multi-seller). `external_seller_id`
     * se respalda de una vez para todos los negocios existentes: debe ser
     * estable desde el día uno y nunca derivarse del slug (que sí cambia).
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('external_seller_id')->nullable()->unique()->after('slug');
            $table->boolean('google_merchant_enabled')->default(false)->after('external_seller_id');
            $table->enum('google_merchant_status', ['no_configurado', 'pendiente', 'activo', 'error'])
                ->default('no_configurado')
                ->after('google_merchant_enabled');
            $table->timestamp('google_merchant_last_sync_at')->nullable()->after('google_merchant_status');
            $table->text('google_merchant_error')->nullable()->after('google_merchant_last_sync_at');
        });

        DB::table('businesses')->whereNull('external_seller_id')->orderBy('id')->chunkById(200, function ($businesses): void {
            foreach ($businesses as $business) {
                DB::table('businesses')
                    ->where('id', $business->id)
                    ->update(['external_seller_id' => Business::externalSellerIdFor($business->id)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'external_seller_id',
                'google_merchant_enabled',
                'google_merchant_status',
                'google_merchant_last_sync_at',
                'google_merchant_error',
            ]);
        });
    }
};

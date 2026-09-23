<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('google_merchant_last_sync_at')->nullable()->change();
            $table->text('google_merchant_last_error')->nullable()->change();
            $table->string('google_merchant_synced_hash')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Estos campos deben seguir siendo opcionales para productos sin sincronizar.
    }
};

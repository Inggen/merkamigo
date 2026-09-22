<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('google_merchant_product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Los productos sin sincronizar necesitan conservar el valor NULL.
    }
};

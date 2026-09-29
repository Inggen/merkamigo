<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_media', function (Blueprint $table) {
            $table->string('type', 20)->default('image')->after('path');
            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('product_media', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'type']);
            $table->dropColumn('type');
        });
    }
};

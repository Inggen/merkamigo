<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', fn (Blueprint $table) => $table->string('product_view_style', 16)->default('classic'));
    }

    public function down(): void
    {
        Schema::table('storefronts', fn (Blueprint $table) => $table->dropColumn('product_view_style'));
    }
};

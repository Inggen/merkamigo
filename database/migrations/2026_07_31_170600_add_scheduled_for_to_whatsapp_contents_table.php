<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_contents', function (Blueprint $table) {
            $table->date('scheduled_for')->nullable()->after('tone');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_contents', function (Blueprint $table) {
            $table->dropColumn('scheduled_for');
        });
    }
};

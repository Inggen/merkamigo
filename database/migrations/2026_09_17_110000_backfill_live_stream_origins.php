<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('live_streams')
            ->whereNull('stream_path')
            ->update(['stream_origin' => 'legacy']);
    }

    public function down(): void
    {
        // La procedencia heredada no debe volver a etiquetarse como propia.
    }
};

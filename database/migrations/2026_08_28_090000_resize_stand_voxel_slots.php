<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templateIds = DB::table('immersive_object_templates')
            ->where('slug', 'stand-voxel')
            ->pluck('id');

        if ($templateIds->isEmpty()) {
            return;
        }

        $slotIds = DB::table('stand_assignments')
            ->whereIn('object_template_id', $templateIds)
            ->whereNotNull('stand_slot_id')
            ->pluck('stand_slot_id');

        if ($slotIds->isEmpty()) {
            return;
        }

        DB::table('stand_slots')
            ->whereIn('id', $slotIds)
            ->update([
                'max_width' => 5,
                'max_depth' => 5,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se revierte automáticamente: el tamaño anterior puede haber
        // sido personalizado por el administrador para cada plaza.
    }
};

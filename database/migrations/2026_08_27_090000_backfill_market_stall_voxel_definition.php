<?php

use App\Domain\Immersive\Support\StandardVoxelDefinitions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = DB::table('immersive_object_templates')
            ->where('slug', 'stand-toldo-mercado')
            ->first(['id', 'model_definition']);

        if (! $template || filled($template->model_definition)) {
            return;
        }

        $definition = StandardVoxelDefinitions::marketStall();

        DB::table('immersive_object_templates')
            ->where('id', $template->id)
            ->update([
                'builder_key' => 'stand',
                'asset_input_mode' => 'ia_voxel',
                'model_definition' => json_encode($definition, JSON_THROW_ON_ERROR),
                'ai_draft_definition' => json_encode($definition, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // La definición puede haber sido personalizada desde el editor
        // después del backfill; un rollback no debe borrar ese trabajo.
    }
};

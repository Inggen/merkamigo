<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->json('scale_vector')->nullable()->after('scale');
        });

        DB::table('immersive_plaza_props')
            ->select(['id', 'scale'])
            ->orderBy('id')
            ->chunkById(100, function ($props): void {
                foreach ($props as $prop) {
                    $scale = (float) ($prop->scale ?: 1);

                    DB::table('immersive_plaza_props')
                        ->where('id', $prop->id)
                        ->update([
                            'scale_vector' => json_encode([
                                'x' => $scale,
                                'y' => $scale,
                                'z' => $scale,
                            ]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('immersive_plaza_props', function (Blueprint $table) {
            $table->dropColumn('scale_vector');
        });
    }
};

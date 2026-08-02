<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega la clave `max_storefronts` (máximo de vitrinas por dueño) a los
 * `limits` json ya sembrados, para que el plan "Gratis" tenga un tope de
 * arranque coherente en vez de quedar sin definir. No crea columnas: es
 * una nueva clave dentro del mismo campo `limits`, igual que
 * `max_products`/`max_members`/`max_featured_days`.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('plans')->get() as $plan) {
            $limits = json_decode($plan->limits ?? '{}', true) ?: [];

            if (array_key_exists('max_storefronts', $limits)) {
                continue;
            }

            $limits['max_storefronts'] = $plan->slug === 'gratis' ? 1 : null;

            DB::table('plans')->where('id', $plan->id)->update([
                'limits' => json_encode($limits),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('plans')->get() as $plan) {
            $limits = json_decode($plan->limits ?? '{}', true) ?: [];
            unset($limits['max_storefronts']);

            DB::table('plans')->where('id', $plan->id)->update([
                'limits' => json_encode($limits),
            ]);
        }
    }
};

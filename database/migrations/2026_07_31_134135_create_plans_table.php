<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('billing_period')->default('mensual');
            $table->json('limits')->nullable();
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // El plan "Gratis" se crea aquí (no solo en el seeder) para que
        // exista siempre, incluso en bases de datos de prueba que corren
        // migraciones sin seeders (`RefreshDatabase`) — `Business::activePlan()`
        // depende de que este plan nunca falte.
        DB::table('plans')->insert([
            'slug' => 'gratis',
            'name' => 'Gratis',
            'description' => 'Vitrina básica para empezar a vender en Merkamigo.',
            'price_cents' => null,
            'billing_period' => 'mensual',
            'limits' => json_encode(['max_products' => 10, 'max_members' => null, 'max_featured_days' => 0]),
            'trial_days' => 0,
            'is_active' => true,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

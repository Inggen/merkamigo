<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MunicipalitySeeder::class,
            ImmersiveObjectTemplateSeeder::class,
            ImmersiveExperienceSeeder::class,
            CategorySeeder::class,
            BusinessAttributeSeeder::class,
            PlanSeeder::class,
            BillingProductSeeder::class,
        ]);

        if (app()->isLocal()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}

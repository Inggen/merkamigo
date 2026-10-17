<?php

namespace Tests\Feature\Marketplace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommissionRetryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_repairs_missing_columns_and_can_run_again(): void
    {
        Schema::table('commission_charges', fn ($table) => $table->dropColumn(['retry_count', 'next_retry_at']));

        $migration = require database_path('migrations/2026_10_09_100000_add_retry_fields_to_commission_charges_table.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumns('commission_charges', ['retry_count', 'next_retry_at']));
    }

    public function test_it_repairs_a_partially_applied_migration(): void
    {
        Schema::table('commission_charges', fn ($table) => $table->dropColumn('next_retry_at'));

        $migration = require database_path('migrations/2026_10_09_100000_add_retry_fields_to_commission_charges_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('commission_charges', ['retry_count', 'next_retry_at']));
    }
}

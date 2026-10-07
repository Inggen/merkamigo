<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        DB::table('loyalty_rewards')
            ->select(['id', 'title'])
            ->orderBy('id')
            ->each(function (object $reward): void {
                $base = Str::slug($reward->title) ?: 'premio-'.$reward->id;
                $slug = $base;
                $suffix = 2;

                while (DB::table('loyalty_rewards')->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }

                DB::table('loyalty_rewards')->where('id', $reward->id)->update(['slug' => $slug]);
            });

        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_rewards', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};

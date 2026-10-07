<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FACEBOOK = 'https://www.facebook.com/profile.php?id=61592810691144';

    private const INSTAGRAM = 'https://www.instagram.com/merkamigos/';

    public function up(): void
    {
        $settingsId = DB::table('site_settings')->orderBy('id')->value('id');
        $values = [
            'social_facebook_url' => self::FACEBOOK,
            'social_instagram_url' => self::INSTAGRAM,
            'updated_at' => now(),
        ];

        if ($settingsId) {
            DB::table('site_settings')->where('id', $settingsId)->update($values);

            return;
        }

        DB::table('site_settings')->insert($values + ['created_at' => now()]);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('social_facebook_url', self::FACEBOOK)
            ->where('social_instagram_url', self::INSTAGRAM)
            ->update([
                'social_facebook_url' => null,
                'social_instagram_url' => null,
                'updated_at' => now(),
            ]);
    }
};

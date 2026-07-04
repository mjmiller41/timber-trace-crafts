<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add YouTube and LinkedIn social URL settings so the admin Social Media
     * form renders inputs for them. insertOrIgnore keeps this idempotent and
     * non-destructive: existing rows (e.g. values the founder has already set)
     * are never overwritten. Values are populated later via admin settings.
     */
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['key' => 'social.youtube_url',  'value' => '', 'group' => 'social', 'label' => 'YouTube URL',  'updated_at' => now()],
            ['key' => 'social.linkedin_url', 'value' => '', 'group' => 'social', 'label' => 'LinkedIn URL', 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'social.youtube_url',
            'social.linkedin_url',
        ])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PNG paths → JPG (optimization script deleted all PNGs and created JPG equivalents)
        DB::table('media')
            ->where('disk', 'r2')
            ->where('path', 'like', '%.png')
            ->update([
                'path' => DB::raw("REPLACE(path, '.png', '.jpg')"),
                'mime_type' => 'image/jpeg',
            ]);

        // JPEG paths → JPG (optimization script renamed .jpeg → .jpg)
        DB::table('media')
            ->where('disk', 'r2')
            ->where('path', 'like', '%.jpeg')
            ->update([
                'path' => DB::raw("REPLACE(path, '.jpeg', '.jpg')"),
                'mime_type' => 'image/jpeg',
            ]);
    }

    public function down(): void
    {
        // Cannot reliably reverse — original extensions are unknown after normalisation
    }
};

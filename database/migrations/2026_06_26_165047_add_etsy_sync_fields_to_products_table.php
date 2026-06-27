<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('etsy_url')->nullable()->after('etsy_readiness_state_id');
            $table->string('etsy_language')->nullable()->after('etsy_url');
            $table->boolean('etsy_is_personalizable')->nullable()->default(false)->after('etsy_language');
            $table->boolean('etsy_has_variations')->nullable()->default(false)->after('etsy_is_personalizable');
            $table->unsignedInteger('etsy_views')->nullable()->after('etsy_has_variations');
            $table->unsignedInteger('etsy_num_favorers')->nullable()->after('etsy_views');
            $table->timestamp('etsy_ending_timestamp')->nullable()->after('etsy_num_favorers');
            $table->timestamp('etsy_last_synced_at')->nullable()->after('etsy_ending_timestamp');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'etsy_url',
                'etsy_language',
                'etsy_is_personalizable',
                'etsy_has_variations',
                'etsy_views',
                'etsy_num_favorers',
                'etsy_ending_timestamp',
                'etsy_last_synced_at',
            ]);
        });
    }
};

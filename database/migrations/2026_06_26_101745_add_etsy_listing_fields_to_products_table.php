<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Master toggle — enables Etsy sync and unlocks Etsy fields in admin UI
            $table->boolean('sold_on_etsy')->default(false)->after('etsy_listing_id');

            // Listing metadata missing from initial migration
            $table->string('etsy_listing_type')->default('physical')->nullable()->after('etsy_state');
            $table->boolean('etsy_is_taxable')->default(true)->nullable()->after('etsy_listing_type');
            $table->boolean('etsy_is_customizable')->default(false)->nullable()->after('etsy_is_taxable');
            $table->boolean('etsy_is_private')->default(false)->nullable()->after('etsy_is_customizable');
            $table->boolean('etsy_should_auto_renew')->default(true)->nullable()->after('etsy_is_private');
            $table->integer('etsy_featured_rank')->nullable()->after('etsy_should_auto_renew');

            // Style tags — Etsy's style taxonomy (stored as JSON array)
            $table->text('etsy_style')->nullable()->after('etsy_featured_rank');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sold_on_etsy',
                'etsy_listing_type',
                'etsy_is_taxable',
                'etsy_is_customizable',
                'etsy_is_private',
                'etsy_should_auto_renew',
                'etsy_featured_rank',
                'etsy_style',
            ]);
        });
    }
};

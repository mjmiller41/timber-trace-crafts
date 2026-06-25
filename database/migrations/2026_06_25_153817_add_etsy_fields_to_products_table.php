<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('etsy_state')->nullable()->after('etsy_listing_id');
            $table->json('etsy_tags')->nullable()->after('etsy_state');
            $table->json('etsy_materials')->nullable()->after('etsy_tags');
            $table->string('etsy_who_made')->nullable()->after('etsy_materials');
            $table->string('etsy_when_made')->nullable()->after('etsy_who_made');
            $table->boolean('etsy_is_supply')->nullable()->after('etsy_when_made');
            $table->unsignedSmallInteger('etsy_processing_min')->nullable()->after('etsy_is_supply');
            $table->unsignedSmallInteger('etsy_processing_max')->nullable()->after('etsy_processing_min');
            $table->float('etsy_item_weight')->nullable()->after('etsy_processing_max');
            $table->string('etsy_item_weight_unit', 10)->nullable()->after('etsy_item_weight');
            $table->float('etsy_item_length')->nullable()->after('etsy_item_weight_unit');
            $table->float('etsy_item_width')->nullable()->after('etsy_item_length');
            $table->float('etsy_item_height')->nullable()->after('etsy_item_width');
            $table->string('etsy_item_dimensions_unit', 10)->nullable()->after('etsy_item_height');
            $table->unsignedBigInteger('etsy_taxonomy_id')->nullable()->after('etsy_item_dimensions_unit');
            $table->unsignedBigInteger('etsy_shop_section_id')->nullable()->after('etsy_taxonomy_id');
            $table->unsignedBigInteger('etsy_shipping_profile_id')->nullable()->after('etsy_shop_section_id');
            $table->unsignedBigInteger('etsy_return_policy_id')->nullable()->after('etsy_shipping_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'etsy_state', 'etsy_tags', 'etsy_materials', 'etsy_who_made', 'etsy_when_made',
                'etsy_is_supply', 'etsy_processing_min', 'etsy_processing_max',
                'etsy_item_weight', 'etsy_item_weight_unit', 'etsy_item_length',
                'etsy_item_width', 'etsy_item_height', 'etsy_item_dimensions_unit',
                'etsy_taxonomy_id', 'etsy_shop_section_id', 'etsy_shipping_profile_id', 'etsy_return_policy_id',
            ]);
        });
    }
};

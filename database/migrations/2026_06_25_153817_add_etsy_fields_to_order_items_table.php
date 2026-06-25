<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('etsy_transaction_id')->nullable()->unique()->after('id');
            $table->datetime('etsy_expected_ship_date')->nullable()->after('etsy_transaction_id');
            $table->decimal('etsy_buyer_coupon', 10, 2)->nullable()->after('etsy_expected_ship_date');
            $table->decimal('etsy_shop_coupon', 10, 2)->nullable()->after('etsy_buyer_coupon');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['etsy_transaction_id', 'etsy_expected_ship_date', 'etsy_buyer_coupon', 'etsy_shop_coupon']);
        });
    }
};

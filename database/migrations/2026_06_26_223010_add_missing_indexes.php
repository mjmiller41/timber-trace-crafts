<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('status');
            $table->index('featured');
            $table->index('created_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('variation_type_id');
            $table->index('is_enabled');
        });

        Schema::table('product_media', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('variant_id');
            $table->index('media_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('variant_id');
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('user_id');
            $table->index('order_item_id');
            $table->index('status');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index('active');
            $table->index('expires_at');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index('uploaded_by');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('image_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['featured']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['variation_type_id']);
            $table->dropIndex(['is_enabled']);
        });

        Schema::table('product_media', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['variant_id']);
            $table->dropIndex(['media_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['variant_id']);
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['order_item_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['uploaded_by']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['image_id']);
        });
    }
};

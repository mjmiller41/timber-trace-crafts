<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->string('etsy_transaction_id')->nullable()->unique()->after('id');
            $table->string('source')->default('direct')->after('etsy_transaction_id'); // 'direct' or 'etsy'
            $table->string('etsy_image_url')->nullable()->after('source');
            $table->string('language', 10)->nullable()->after('etsy_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn(['etsy_transaction_id', 'source', 'etsy_image_url', 'language']);
        });
    }
};

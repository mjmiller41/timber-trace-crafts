<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('message_from_buyer')->nullable()->after('etsy_receipt_id');
            $table->boolean('etsy_is_paid')->nullable()->after('message_from_buyer');
            $table->boolean('etsy_is_shipped')->nullable()->after('etsy_is_paid');
            $table->string('etsy_payment_method')->nullable()->after('etsy_is_shipped');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['message_from_buyer', 'etsy_is_paid', 'etsy_is_shipped', 'etsy_payment_method']);
        });
    }
};

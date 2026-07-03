<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_refund_id')->nullable()->after('stripe_payment_intent_id');
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('stripe_refund_id');
            $table->timestamp('refunded_at')->nullable()->after('refunded_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stripe_refund_id', 'refunded_amount', 'refunded_at']);
        });
    }
};

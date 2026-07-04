<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            // Stripe PaymentIntent id for a self-service online purchase. Null for
            // cards issued by CLI/admin. Unique so a redelivered webhook can never
            // issue the same card twice.
            $table->string('purchase_payment_intent_id')->nullable()->unique()->after('purchaser_email');
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropUnique(['purchase_payment_intent_id']);
            $table->dropColumn('purchase_payment_intent_id');
        });
    }
};

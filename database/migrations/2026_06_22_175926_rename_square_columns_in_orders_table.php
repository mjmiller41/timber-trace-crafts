<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('square_payment_id', 'stripe_payment_intent_id');
            $table->dropColumn('square_order_id');
        });

        // Replace Square settings with Stripe settings
        DB::table('settings')->whereIn('key', [
            'square.environment',
            'square.sandbox_app_id',
            'square.sandbox_access_token',
            'square.production_app_id',
            'square.production_access_token',
            'square.location_id',
        ])->delete();

        DB::table('settings')->insert([
            ['key' => 'stripe.publishable_key', 'value' => '', 'group' => 'payments', 'label' => 'Stripe Publishable Key', 'updated_at' => now()],
            ['key' => 'stripe.mode',            'value' => 'test', 'group' => 'payments', 'label' => 'Stripe Mode (test/live)', 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('stripe_payment_intent_id', 'square_payment_id');
            $table->string('square_order_id')->nullable();
        });

        DB::table('settings')->whereIn('key', [
            'stripe.publishable_key',
            'stripe.mode',
        ])->delete();
    }
};

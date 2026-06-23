<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->enum('status', [
                'pending_payment',
                'processing',
                'in_production',
                'shipped',
                'delivered',
                'refunded',
                'cancelled',
            ])->default('pending_payment');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code_snapshot')->nullable();
            $table->string('square_payment_id')->nullable();
            $table->string('square_order_id')->nullable();
            $table->string('shipping_method')->nullable();
            $table->text('gift_message')->nullable();
            // Shipping address snapshot
            $table->string('shipping_first_name');
            $table->string('shipping_last_name');
            $table->string('shipping_line1');
            $table->string('shipping_line2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_zip');
            $table->string('shipping_country')->default('US');
            $table->string('shipping_phone')->nullable();
            // Billing address snapshot
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_line1');
            $table->string('billing_line2')->nullable();
            $table->string('billing_city');
            $table->string('billing_state');
            $table->string('billing_zip');
            $table->string('billing_country')->default('US');
            $table->string('ip_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

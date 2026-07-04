<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // Session-side identifier: stored in the visitor's session so a
            // single browser maps to exactly one persisted cart row.
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->json('contents');
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            // One-click unsubscribe token, unique per cart so the link never
            // exposes the email address.
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamp('last_activity_at')->nullable();
            // Idempotency: which reminder stage (0 = none, 1 = first, 2 = final)
            // has already been sent, plus when the last one went out.
            $table->unsignedTinyInteger('reminder_stage')->default(0);
            $table->timestamp('reminder_sent_at')->nullable();
            // Set when the cart becomes an order; converted carts are never
            // reminded and are excluded from the abandonment sweep.
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('last_activity_at');
            $table->index(['converted_at', 'reminder_stage', 'last_activity_at'], 'carts_sweep_index');
        });

        // Suppression list: an email here is never sent an abandoned-cart
        // reminder again (one-click unsubscribe / CAN-SPAM opt-out). Kept
        // separate from carts so a single opt-out covers every cart the
        // address ever creates.
        Schema::create('cart_email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason', 40)->default('unsubscribe');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_email_suppressions');
        Schema::dropIfExists('carts');
    }
};

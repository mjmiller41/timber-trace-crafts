<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            // amount is signed: positive = credit (issue/refund), negative = redeem.
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->enum('type', ['issue', 'redeem', 'refund', 'adjust']);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['gift_card_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
    }
};

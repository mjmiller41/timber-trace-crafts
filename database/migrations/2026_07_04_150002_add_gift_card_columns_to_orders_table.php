<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('gift_card_id')->nullable()->after('coupon_code_snapshot')
                ->constrained('gift_cards')->nullOnDelete();
            $table->decimal('gift_card_amount', 10, 2)->default(0)->after('gift_card_id');
            $table->string('gift_card_code_snapshot')->nullable()->after('gift_card_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
            $table->dropColumn(['gift_card_amount', 'gift_card_code_snapshot']);
        });
    }
};

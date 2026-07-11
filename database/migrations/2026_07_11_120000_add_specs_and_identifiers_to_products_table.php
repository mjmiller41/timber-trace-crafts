<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('meta_description');
            $table->string('gtin13', 14)->nullable()->after('specs');
            $table->boolean('identifier_exists')->default(true)->after('gtin13');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['specs', 'gtin13', 'identifier_exists']);
        });
    }
};

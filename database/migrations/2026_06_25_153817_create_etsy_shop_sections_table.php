<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etsy_shop_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('etsy_section_id')->unique();
            $table->string('title');
            $table->unsignedSmallInteger('rank')->nullable();
            $table->unsignedInteger('active_listing_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etsy_shop_sections');
    }
};

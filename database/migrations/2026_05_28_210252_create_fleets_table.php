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
        // اگر جدول fleets از قبل در دیتابیس نشسته بود، skip کن
        if (!Schema::hasTable('fleets')) {
            Schema::create('fleets', function (Blueprint $table) {
                $table->id();
                $table->string('smart_id')->unique();
                $table->string('bargir_name')->nullable();
                $table->string('vin_code')->nullable();
                $table->string('tarikh_moayene')->nullable();
                $table->string('plq1')->nullable();
                $table->string('plq2')->default('ع');
                $table->string('plq3')->nullable();
                $table->string('serial')->nullable();
                $table->string('transit_horse_num')->nullable();
                $table->string('transit_trailer_num')->nullable();
                $table->string('is_active')->default('فعال');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};
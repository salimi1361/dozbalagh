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
        Schema::create('driver_locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('driver_id')->constrained()->onDelete('cascade');
    $table->foreignId('dozbalagh_item_id')->constrained()->onDelete('cascade');
    $table->decimal('latitude', 10, 8);
    $table->decimal('longitude', 11, 8);
    $table->integer('speed')->nullable(); // سرعت حرکت راننده (برای تحلیل‌های لجستیکی آینده)
    $table->timestamp('recorded_at'); // زمان ثبت لوکیشن توسط گوشی راننده
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};

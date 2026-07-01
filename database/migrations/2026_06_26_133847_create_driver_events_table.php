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
        Schema::create('driver_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('driver_id')->constrained()->onDelete('cascade');
    $table->foreignId('dozbalagh_item_id')->constrained()->onDelete('cascade'); // متصل به آیتم دوزبلاغ راننده
    $table->string('event_type'); // مثلا: 'started_trip', 'reached_border', 'delivered'
    $table->string('description')->nullable(); // توضیحات راننده (مثلا: معطلی در گمرک)
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    $table->timestamps(); // این زمان دقیق رویداد را به ما می‌دهد
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_events');
    }
};

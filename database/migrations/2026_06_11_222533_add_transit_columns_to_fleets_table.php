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
    Schema::table('fleets', function (Blueprint $table) {
        // اضافه کردن ستون‌ها به صورت nullable که اگر خالی هم بودند ارور ندهد
        $table->string('transit_horse')->nullable()->after('truck_type');
        $table->string('transit_trailer')->nullable()->after('transit_horse');
    });
}

public function down(): void
{
    Schema::table('fleets', function (Blueprint $table) {
        $table->dropColumn(['transit_horse', 'transit_trailer']);
    });
}
};

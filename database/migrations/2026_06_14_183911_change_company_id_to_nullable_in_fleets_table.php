<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            // تغییر ستون به حالت nullable
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            // بازگرداندن به حالت اجباری در صورت نیاز
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
    }
};

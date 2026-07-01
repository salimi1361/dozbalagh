<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            // اضافه کردن کلید توقف/اجازه توزیع کلان (پیش‌فرض: فعال)
            $table->boolean('is_distributing')->default(true)->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('is_distributing');
        });
    }
};
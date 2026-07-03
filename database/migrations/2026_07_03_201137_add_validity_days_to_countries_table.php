<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            // اضافه کردن ستون روز اعتبار بعد از ستون قیمت با پیش‌فرض ۳۰ روز
            $table->integer('validity_days')->default(30)->after('price');
        });
    }

    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('validity_days');
        });
    }
};
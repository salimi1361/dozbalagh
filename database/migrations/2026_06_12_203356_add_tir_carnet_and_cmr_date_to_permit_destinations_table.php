<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('permit_destinations', function (Blueprint $table) {
            // اضافه کردن فیلد تاریخ بارگیری CMR (اجباری - بعد از فیلد فایل سی ام آر)
            $table->date('cmr_date')->after('cmr_file');
            
            // اضافه کردن فیلدهای کارنه تیر (اختیاری - بعد از فیلد تاریخ سی ام آر)
            $table->string('tir_carnet_number')->nullable()->after('cmr_date');
            $table->date('tir_carnet_date')->nullable()->after('tir_carnet_number');
        });
    }

    public function down()
    {
        Schema::table('permit_destinations', function (Blueprint $table) {
            // دستور بازگشت در صورت نیاز به عقبگرد (Rollback)
            $table->dropColumn(['cmr_date', 'tir_carnet_number', 'tir_carnet_date']);
        });
    }
};

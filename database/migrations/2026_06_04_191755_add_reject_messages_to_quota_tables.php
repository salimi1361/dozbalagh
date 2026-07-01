<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // اضافه کردن پیام مسدودی عمومی به کشورها
        Schema::table('countries', function (Blueprint $table) {
            $table->string('reject_message')->nullable()->after('default_quota')->comment('پیام پیش‌فرض در صورت مسدودی');
        });

        // اضافه کردن پیام مسدودی اختصاصی برای شرکت
        Schema::table('company_quotas', function (Blueprint $table) {
            $table->string('reject_message')->nullable()->after('custom_limit')->comment('پیام مسدودی اختصاصی این شرکت');
        });
    }

    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('reject_message');
        });
        Schema::table('company_quotas', function (Blueprint $table) {
            $table->dropColumn('reject_message');
        });
    }
};
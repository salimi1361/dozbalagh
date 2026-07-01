<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // اضافه کردن سهمیه عمومی به جدول کشورها
        Schema::table('countries', function (Blueprint $table) {
            $table->integer('default_quota')->nullable()->after('is_distributing')->comment('سهمیه پیش‌فرض کل. اگر خالی باشد یعنی نامحدود');
        });

        // تغییر فیلد محدودیت اختصاصی شرکت‌ها
        Schema::table('company_quotas', function (Blueprint $table) {
            $table->dropColumn('max_limit'); // حذف فیلد قبلی
        });
        
        Schema::table('company_quotas', function (Blueprint $table) {
            $table->integer('custom_limit')->nullable()->after('country_id')->comment('سهمیه اختصاصی. اگر خالی باشد از پیش‌فرض کشور ارث می‌برد');
        });
    }

    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('default_quota');
        });
        Schema::table('company_quotas', function (Blueprint $table) {
            $table->dropColumn('custom_limit');
            $table->integer('max_limit')->default(0);
        });
    }
};

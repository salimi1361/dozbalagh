<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ۱. اضافه کردن آیدی کشور به جدول دسته‌ها (Batch)
        Schema::table('dozbalagh_batches', function (Blueprint $table) {
            // اضافه کردن country_id
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('cascade')->after('id');
        });

        // ۲. اضافه کردن آیدی انجمن به جدول تک‌شماره‌ها (Item)
        Schema::table('dozbalagh_items', function (Blueprint $table) {
            $table->unsignedBigInteger('association_id')->nullable()->after('batch_id')->comment('انجمنی که دوزبلاغ را در اختیار دارد');
        });
    }

    public function down()
    {
        Schema::table('dozbalagh_batches', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });

        Schema::table('dozbalagh_items', function (Blueprint $table) {
            $table->dropColumn('association_id');
        });
    }
};

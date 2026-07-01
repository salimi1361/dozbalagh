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
        Schema::create('permit_destinations', function (Blueprint $table) {
            $table->id();
            
            // اتصال به جدول اصلی درخواست‌ها
            $table->foreignId('permit_request_id')->constrained('permit_requests')->onDelete('cascade');
            
            // اطلاعات اصلی مقصد
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('permit_type'); // نوع دوزوله
            $table->string('cargo_type'); // نوع بار: صادرات، واردات، ترانزیت
            
            // فیلدهای متنی
            $table->string('loading_origin')->nullable();
            $table->string('loading_destination')->nullable();
            $table->string('cits_code')->nullable();
            $table->string('receipt_code')->nullable(); // کد پیگیری فیش
            
            // مسیر فایل‌های آپلود شده
            $table->string('receipt_file'); // فیش پرداختی (اجباری)
            $table->string('cmr_file'); // تصویر CMR (اجباری)
            $table->string('declaration_file')->nullable(); // اظهارنامه (اختیاری)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permit_destinations');
    }
};

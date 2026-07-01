<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_request_id')->constrained('permit_requests')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries');
            
            // اطلاعات دوزوله درخواستی
            $table->string('permit_type'); // نوع دوزوله
            $table->string('operation_type'); // نوع عملیات حمل: صادرات/واردات/ترانزیت
            $table->decimal('price', 15, 0)->default(0); // قیمت در زمان درخواست
            
            // بخش بررسی و تخصیص توسط انجمن
            $table->string('allocation_status')->default('pending'); // در انتظار، تخصیص‌یافته، ردشده
            $table->string('d_serial_number')->nullable(); // شماره سریال دوزوله تخصیص یافته
            $table->string('rejection_reason')->nullable(); // دلیل رد شدن این ردیف خاص
            
            // بخش برگشت دوزوله توسط شرکت
            $table->string('return_status')->nullable(); // مصرفی، بدون استفاده-ابطال، بدون استفاده-رزرو، مفقودی
            $table->string('return_cmr_file')->nullable(); // فایل سیمر (الزامی در صورت انتخاب حالت مصرفی)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_request_items');
    }
};
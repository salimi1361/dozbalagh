<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_requests', function (Blueprint $table) {
            $table->id();
            $table->string('d_code')->unique(); // کد یکتای پرونده مثل D-20260612001
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fleet_id')->constrained()->cascadeOnDelete();
            
            // اطلاعات مسیر و CITS
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('cits_code')->nullable();
            
            // وضعیت‌ها
            $table->string('status')->default('draft'); // پیش‌نویس، در انتظار بررسی، نیازمند ویرایش، تایید، رد، ابطال
            $table->string('payment_status')->default('unpaid'); // وضعیت پرداخت کلی پرونده
            $table->decimal('total_amount', 15, 0)->default(0); // جمع کل هزینه دوزوله‌های این پرونده
            
            // مدارک پیوست (طبق سناریو)
            $table->string('request_letter_file')->nullable(); // نامه درخواست 
            $table->string('cmr_file')->nullable(); // سیمر / CMR
            $table->string('declaration_file')->nullable(); // اظهارنامه
            $table->string('payment_receipt_file')->nullable(); // فیش پرداختی
            
            // توضیحات و ارتباطات
            $table->text('company_note')->nullable(); // یادداشت شرکت
            $table->text('association_note')->nullable(); // پیام/توضیح انجمن برای نقص مدارک
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_requests');
    }
};
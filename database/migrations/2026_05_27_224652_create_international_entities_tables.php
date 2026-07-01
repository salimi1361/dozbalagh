<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. جدول مشخصات شرکت‌های بین‌المللی
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_code')->unique(); // کد بین‌المللی شرکت از پرگار
            $table->string('name_fa');
            $table->string('name_en'); // نام فینگلیش استاندارد برای پرینت دوزبلاغ
            $table->text('address_fa');
            $table->text('address_en'); // آدرس فینگلیش برای پرینت دوزبلاغ
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // ۲. جدول مشخصات رانندگان ترانزیت (همراه با قفل انحصار شرکتی)
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // قفل انحصار ۱۰۰٪: مشخص می‌کند راننده در انحصار کدام شرکت است (می‌تواند null یعنی آزاد باشد)
            $table->foreignId('current_company_id')->nullable()->constrained('companies')->onDelete('set null');
            
            $table->text('national_code'); // به صورت Encrypted ذخیره خواهد شد
            $table->text('passport_number'); // به صورت Encrypted ذخیره خواهد شد
            $table->string('first_name_fa');
            $table->string('last_name_fa');
            $table->string('first_name_en'); // نام فینگلیش پاسپورتی
            $table->string('last_name_en'); // نام خانوادگی فینگلیش پاسپورتی
            $table->enum('contract_status', ['bound', 'free'])->default('free'); // وضعیت قرارداد راننده
            $table->timestamps();
        });

        // ۳. جدول ناوگان تحت پوشش (کامیون‌های ترانزیت)
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('transit_plate')->unique(); // پلاک بین‌المللی (انگلیسی)
            $table->string('smart_card_number')->nullable(); // شماره کارت هوشمند کامیون
            $table->string('truck_type')->nullable(); // تیپ کامیون
            $table->boolean('is_manual')->default(false); // ثبت دستی شده یا استعلام آنلاین؟
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleets');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('companies');
    }
};
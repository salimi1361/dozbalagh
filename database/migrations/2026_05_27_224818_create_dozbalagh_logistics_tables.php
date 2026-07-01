<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. جدول دسته‌بندی دوزبلاغ کشورها (پارت‌های دریافتی از راهداری)
        Schema::create('dozbalagh_batches', function (Blueprint $table) {
            $table->id();
            $table->string('country_name'); // مثلاً Turkey, Bulgaria, France
            $table->integer('serial_start'); // شماره شروع بازه سریال
            $table->integer('serial_end'); // شماره پایان بازه سریال
            $table->integer('total_quantity'); // تعداد کل برگه‌های این پارت
            $table->date('expiry_date')->nullable(); // انقضای پویا و اختیاری
            $table->enum('status', ['active', 'exhausted'])->default('active'); // وضعیت موجودی دسته
            $table->timestamps();
        });

        // ۲. جدول تک‌برگ‌های دوزبلاغ و مدیریت دقیق چرخه عمر لاشه
        Schema::create('dozbalagh_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('dozbalagh_batches')->onDelete('cascade');
            $table->integer('serial_number')->unique(); // شماره سریال دقیق و منحصربه‌فرد برگه
            
            // تخصیص‌های پویا (تا زمان صدور قطعی null هستند)
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->foreignId('fleet_id')->nullable()->constrained('fleets')->onDelete('set null');
            
            // وضعیت‌های چرخه عمر برگه
            $table->enum('lifecycle_status', [
                'in_stock',        // موجود در انبار انجمن
                'issued',          // صادر شده و در دست شرکت/راننده
                'used',            // مصرف شده (لاشه عودت شد)
                'returned_unused', // مصرف‌نشده (لاشه سالم برگشت و پول عودت شد)
                'extended',        // تمدید شده
                'cancelled'        // ابطال شده
            ])->default('in_stock');
            
            $table->timestamp('issued_at')->nullable(); // زمان صدور
            $table->timestamp('returned_at')->nullable(); // زمان عودت لاشه
            $table->timestamps();
        });

        // ۳. جدول موتور قالب‌ساز پرینت چندتیپه دوزبلاغ (مختصات پیکسلی X و Y)
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('country_name'); // تفکیک بر اساس کشور (مثلاً Turkey_Type_1)
            $table->string('field_name'); // نام فیلد فنی (driver_name, transit_plate, company_name)
            $table->integer('pos_x'); // مختصات افقی روی کاغذ بر حسب پیکسل/میلی‌متر
            $table->integer('pos_y'); // مختصات عمودی روی کاغذ
            $table->integer('font_size')->default(12); // سایز فونت چاپ فیلد
            $table->timestamps();
            
            // یکتایی ترکیب کشور و نام فیلد برای جلوگیری از دیتای تکراری
            $table->unique(['country_name', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_templates');
        Schema::dropIfExists('dozbalagh_items');
        Schema::dropIfExists('dozbalagh_batches');
    }
};
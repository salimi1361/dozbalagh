<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // کاربری که کیف پولش شارژ می‌شود (اگر در سیستم شما شرکت یا شخص دیگری متصل است، آیدی آن را بگذار)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); 
            
            $table->decimal('amount', 15, 0); // مبلغ به ریال 
            $table->string('type')->default('wallet_charge'); // نوع تراکنش: شارژ کیف پول یا کسر از کیف پول
            
            // فیلدهای اختصاصی زرین‌پال
            $table->string('authority')->nullable()->unique(); // شناسه یکتای پرداخت (قبل از پرداخت)
            $table->string('ref_id')->nullable()->unique(); // شماره پیگیری زرین‌پال (بعد از موفقیت)
            
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending'); // وضعیت تراکنش
            $table->string('description')->nullable(); // بابت چه چیزی بوده
            $table->ipAddress('ip_address')->nullable(); // ثبت IP پرداخت‌کننده جهت امنیت
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
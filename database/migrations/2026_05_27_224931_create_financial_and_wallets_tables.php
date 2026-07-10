<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. جدول کیف پول متمرکز شرکت‌های حمل‌ونقل بین‌المللی
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0.00); // مانده حساب کل شرکت به ریال
            $table->decimal('blocked_balance', 15, 2)->default(0.00); // 👈 موجودی مسدود شده / بلوکه شده (اضافه شد)
            $table->timestamps();
        });

        // ۲. جدول دفتر کل حسابداری ریز تراکنش‌ها (Ledger)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            
            // اتصال به دوزوله (در صورت نیاز به رهگیری دقیق ردیف‌ها)
            $table->foreignId('dozbalagh_item_id')->nullable()->constrained('dozbalagh_items')->onDelete('set null');
            
            $table->decimal('amount', 15, 2); // مبلغ تراکنش
            $table->enum('type', ['debit', 'credit']); // بدهکار (کسر وجه) یا بستانکار (افزایش/واریز)
            
            // اکشن‌تایپ دقیق مالی جهت فیلترینگ و حسابرسی واحد مالی
            $table->enum('action_type', [
                'online_charge',      // شارژ آنلاین کیف پول توسط شرکت
                'dozbalagh_reserve',  // 👈 بلوکه شدن وجه در زمان ثبت اولیه درخواست دوزوله
                'dozbalagh_purchase', // کسر قطعی و نهایی وجه پس از تایید انجمن
                'dozbalagh_release',  // 👈 آزادسازی وجه بلوکه شده (در صورت رد درخواست توسط انجمن)
                'dozbalagh_refund',   // بازگشت وجه به کیف پول بابت عودت لاشه استفاده‌نشده
                'manual_adjustment'   // اصلاحیه دستی یا سند دستی توسط حسابدار انجمن
            ]);
            
            // تفکیک ماه شمسی/میلادی
            $table->string('transaction_month', 7); 
            
            $table->text('description')->nullable(); // توضیحات سند مالی (بابت دوزوله شماره X)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};

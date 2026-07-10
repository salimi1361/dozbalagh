<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::create('settlements', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('amount'); // مبلغ تسویه به ریال
        $table->string('ref_number'); // شماره فیش / پیگیری بانکی
        $table->string('bank_name'); // نام بانک مقصد
        $table->string('receipt_file')->nullable(); // مسیر ذخیره تصویر فیش واریزی
        $table->text('description')->nullable(); // توضیحات اختیاری ادمین
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};

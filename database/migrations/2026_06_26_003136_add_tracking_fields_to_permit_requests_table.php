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
    Schema::table('permit_requests', function (Blueprint $table) {
        $table->timestamp('approved_at')->nullable()->comment('تاریخ و ساعت تایید اولیه انجمن');
        $table->timestamp('returned_at')->nullable()->comment('تاریخ و ساعت برگشت به شرکت');
        $table->timestamp('rejected_at')->nullable()->comment('تاریخ و ساعت رد قطعی');
        $table->text('reject_reason')->nullable()->comment('علت رد یا برگشت پرونده');
        $table->unsignedBigInteger('action_by_user_id')->nullable()->comment('کد کاربری ادمین بررسی کننده');
    });
}

public function down()
{
    Schema::table('permit_requests', function (Blueprint $table) {
        $table->dropColumn(['approved_at', 'returned_at', 'rejected_at', 'reject_reason', 'action_by_user_id']);
    });
}
};

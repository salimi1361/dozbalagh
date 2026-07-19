<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shahbaz_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('license_request_id');
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('pending_payment');
            $table->text('description')->nullable();
            $table->foreignId('approved_by_user_id')->nullable();
            $table->dateTime('approved_at');
            $table->string('gateway_authority', 100)->nullable()->unique();
            $table->string('gateway_reference', 100)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id', 'shbz_pay_company_fk')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('license_request_id', 'shbz_pay_request_fk')->references('id')->on('shahbaz_license_requests')->cascadeOnDelete();
            $table->foreign('approved_by_user_id', 'shbz_pay_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'status'], 'shbz_pay_company_status_idx');
            $table->index(['license_request_id', 'status'], 'shbz_pay_request_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_payment_requests');
    }
};

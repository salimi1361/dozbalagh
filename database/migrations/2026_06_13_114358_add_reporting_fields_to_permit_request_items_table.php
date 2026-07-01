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
        Schema::table('permit_request_items', function (Blueprint $table) {
            // فیلدهای متنی ناوبری و سامانه‌ای به ازای هر مقصد
            $table->string('loading_origin')->nullable()->after('operation_type');
            $table->string('loading_destination')->nullable()->after('loading_origin');
            $table->string('cits_code')->nullable()->after('loading_destination');
            $table->string('trip_code')->nullable()->after('cits_code'); // کد سفر شروع با J
            
            // فیلدهای فیش سازمان
            $table->string('receipt_code')->nullable()->after('trip_code');
            $table->unsignedBigInteger('receipt_amount')->default(0)->after('receipt_code');
            $table->string('receipt_file')->nullable()->after('receipt_amount');
            
            // فیلدهای CMR و کارنه تیر
            $table->date('cmr_date')->nullable()->after('receipt_file');
            $table->string('cmr_file')->nullable()->after('cmr_date');
            $table->string('tir_carnet_number')->nullable()->after('cmr_file');
            $table->date('tir_carnet_date')->nullable()->after('tir_carnet_number');
            $table->string('tir_file')->nullable()->after('tir_carnet_date');
            $table->string('declaration_file')->nullable()->after('tir_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->dropColumn([
                'loading_origin', 'loading_destination', 'cits_code', 'trip_code',
                'receipt_code', 'receipt_amount', 'receipt_file',
                'cmr_date', 'cmr_file', 'tir_carnet_number', 'tir_carnet_date', 'tir_file',
                'declaration_file'
            ]);
        });
    }
};
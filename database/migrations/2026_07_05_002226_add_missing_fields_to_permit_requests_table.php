<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('permit_requests', 'cargo_type')) {
                $table->string('cargo_type', 100)->nullable()->after('destination');
            }
            if (!Schema::hasColumn('permit_requests', 'receipt_code')) {
                $table->string('receipt_code', 150)->nullable()->after('cits_code');
            }
            if (!Schema::hasColumn('permit_requests', 'trip_code')) {
                $table->string('trip_code', 150)->nullable()->after('receipt_code');
            }
            if (!Schema::hasColumn('permit_requests', 'cmr_date')) {
                $table->date('cmr_date')->nullable()->after('trip_code');
            }
            if (!Schema::hasColumn('permit_requests', 'tir_carnet_number')) {
                $table->string('tir_carnet_number', 150)->nullable()->after('cmr_date');
            }
            if (!Schema::hasColumn('permit_requests', 'tir_carnet_date')) {
                $table->date('tir_carnet_date')->nullable()->after('tir_carnet_number');
            }
            if (!Schema::hasColumn('permit_requests', 'tir_file')) {
                $table->string('tir_file', 255)->nullable()->after('payment_receipt_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            $table->dropColumn([
                'cargo_type', 'receipt_code', 'trip_code', 
                'cmr_date', 'tir_carnet_number', 'tir_carnet_date', 'tir_file'
            ]);
        });
    }
};
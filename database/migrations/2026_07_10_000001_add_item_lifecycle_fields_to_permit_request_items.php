<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('permit_request_items', 'item_status')) {
                $table->string('item_status')->default('pending')->after('allocation_status');
            }

            if (!Schema::hasColumn('permit_request_items', 'issued_at')) {
                $table->timestamp('issued_at')->nullable()->after('d_serial_number');
            }

            if (!Schema::hasColumn('permit_request_items', 'validity_days')) {
                $table->unsignedInteger('validity_days')->nullable()->after('issued_at');
            }

            if (!Schema::hasColumn('permit_request_items', 'permit_valid_until')) {
                $table->date('permit_valid_until')->nullable()->after('validity_days');
            }

            if (!Schema::hasColumn('permit_request_items', 'renewed_from_item_id')) {
                $table->unsignedBigInteger('renewed_from_item_id')->nullable()->after('permit_valid_until');
            }

            if (!Schema::hasColumn('permit_request_items', 'company_return_image')) {
                $table->string('company_return_image')->nullable()->after('renewed_from_item_id');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_name')) {
                $table->string('courier_name')->nullable()->after('company_return_image');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_mobile')) {
                $table->string('courier_mobile', 30)->nullable()->after('courier_name');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_national_code')) {
                $table->string('courier_national_code', 30)->nullable()->after('courier_mobile');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_vehicle_plate')) {
                $table->string('courier_vehicle_plate', 100)->nullable()->after('courier_national_code');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_delivery_code')) {
                $table->string('courier_delivery_code', 20)->nullable()->after('courier_vehicle_plate');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_code_sent_at')) {
                $table->timestamp('courier_code_sent_at')->nullable()->after('courier_delivery_code');
            }

            if (!Schema::hasColumn('permit_request_items', 'company_return_submitted_at')) {
                $table->timestamp('company_return_submitted_at')->nullable()->after('courier_code_sent_at');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_received_at')) {
                $table->timestamp('courier_received_at')->nullable()->after('company_return_submitted_at');
            }

            if (!Schema::hasColumn('permit_request_items', 'courier_received_by_user_id')) {
                $table->unsignedBigInteger('courier_received_by_user_id')->nullable()->after('courier_received_at');
            }

            if (!Schema::hasColumn('permit_request_items', 'lost_reported_at')) {
                $table->timestamp('lost_reported_at')->nullable()->after('courier_received_by_user_id');
            }

            if (!Schema::hasColumn('permit_request_items', 'lost_reason')) {
                $table->text('lost_reason')->nullable()->after('lost_reported_at');
            }

            if (!Schema::hasColumn('permit_request_items', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('lost_reason');
            }

            if (!Schema::hasColumn('permit_request_items', 'collected_image')) {
                $table->string('collected_image')->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $columns = [
                'item_status',
                'issued_at',
                'validity_days',
                'permit_valid_until',
                'renewed_from_item_id',
                'company_return_image',
                'courier_name',
                'courier_mobile',
                'courier_national_code',
                'courier_vehicle_plate',
                'courier_delivery_code',
                'courier_code_sent_at',
                'company_return_submitted_at',
                'courier_received_at',
                'courier_received_by_user_id',
                'lost_reported_at',
                'lost_reason',
                'closed_at',
                'collected_image',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('permit_request_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

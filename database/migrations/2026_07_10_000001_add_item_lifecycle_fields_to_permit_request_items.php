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
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('permit_request_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

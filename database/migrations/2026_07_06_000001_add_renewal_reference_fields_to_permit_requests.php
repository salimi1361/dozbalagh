<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('permit_requests', 'previous_request_id')) {
                $table->unsignedBigInteger('previous_request_id')->nullable()->after('serial_number');
            }

            if (!Schema::hasColumn('permit_requests', 'previous_d_code')) {
                $table->string('previous_d_code')->nullable()->after('previous_request_id');
            }

            if (!Schema::hasColumn('permit_requests', 'previous_serial_number')) {
                $table->string('previous_serial_number')->nullable()->after('previous_d_code');
            }

            if (!Schema::hasColumn('permit_requests', 'request_type')) {
                $table->string('request_type')->default('new')->after('previous_serial_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (Schema::hasColumn('permit_requests', 'request_type')) {
                $table->dropColumn('request_type');
            }

            if (Schema::hasColumn('permit_requests', 'previous_serial_number')) {
                $table->dropColumn('previous_serial_number');
            }

            if (Schema::hasColumn('permit_requests', 'previous_d_code')) {
                $table->dropColumn('previous_d_code');
            }

            if (Schema::hasColumn('permit_requests', 'previous_request_id')) {
                $table->dropColumn('previous_request_id');
            }
        });
    }
};

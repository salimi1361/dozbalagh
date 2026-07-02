<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('permit_requests', 'issued_at')) {
                $table->timestamp('issued_at')->nullable()->after('serial_number')->index();
            }

            if (!Schema::hasColumn('permit_requests', 'validity_days')) {
                $table->unsignedInteger('validity_days')->nullable()->after('issued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (Schema::hasColumn('permit_requests', 'validity_days')) {
                $table->dropColumn('validity_days');
            }

            if (Schema::hasColumn('permit_requests', 'issued_at')) {
                $table->dropColumn('issued_at');
            }
        });
    }
};

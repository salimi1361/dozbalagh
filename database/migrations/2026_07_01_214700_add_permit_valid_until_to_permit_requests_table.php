<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('permit_requests', 'permit_valid_until')) {
                $table->date('permit_valid_until')->nullable()->after('serial_number')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            if (Schema::hasColumn('permit_requests', 'permit_valid_until')) {
                $table->dropColumn('permit_valid_until');
            }
        });
    }
};

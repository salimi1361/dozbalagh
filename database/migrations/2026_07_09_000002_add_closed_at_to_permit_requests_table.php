<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('permit_requests', 'closed_at')) {
            Schema::table('permit_requests', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->after('courier_received_by_user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('permit_requests', 'closed_at')) {
            Schema::table('permit_requests', function (Blueprint $table) {
                $table->dropColumn('closed_at');
            });
        }
    }
};

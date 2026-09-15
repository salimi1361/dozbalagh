<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('permit_request_items', 'expiry_reminder_sent_at')) {
                $table->timestamp('expiry_reminder_sent_at')->nullable()->after('permit_valid_until')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('permit_request_items', 'expiry_reminder_sent_at')) {
                $table->dropColumn('expiry_reminder_sent_at');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('permit_requests', 'company_request_note')) {
            return;
        }

        Schema::table('permit_requests', function (Blueprint $table) {
            $table->text('company_request_note')->nullable()->after('company_note');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('permit_requests', 'company_request_note')) {
            return;
        }

        Schema::table('permit_requests', function (Blueprint $table) {
            $table->dropColumn('company_request_note');
        });
    }
};

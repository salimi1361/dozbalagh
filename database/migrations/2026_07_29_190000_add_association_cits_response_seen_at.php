<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->timestamp('association_cits_response_seen_at')
                ->nullable()
                ->after('company_cits_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->dropColumn('association_cits_response_seen_at');
        });
    }
};

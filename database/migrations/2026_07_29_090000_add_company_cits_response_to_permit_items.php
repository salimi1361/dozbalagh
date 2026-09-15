<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->text('company_cits_response')->nullable()->after('cits_attempted_at');
            $table->string('company_cits_response_file')->nullable()->after('company_cits_response');
            $table->foreignId('company_cits_responded_by_user_id')->nullable()->after('company_cits_response_file')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('company_cits_responded_at')->nullable()->after('company_cits_responded_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_cits_responded_by_user_id');
            $table->dropColumn([
                'company_cits_response',
                'company_cits_response_file',
                'company_cits_responded_at',
            ]);
        });
    }
};

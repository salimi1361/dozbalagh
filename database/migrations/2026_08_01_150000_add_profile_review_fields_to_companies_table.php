<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'profile_submitted_at')) {
                $table->timestamp('profile_submitted_at')->nullable()->after('is_association_member');
            }
            if (! Schema::hasColumn('companies', 'profile_review_note')) {
                $table->text('profile_review_note')->nullable()->after('profile_submitted_at');
            }
            if (! Schema::hasColumn('companies', 'profile_reviewed_by_user_id')) {
                $table->foreignId('profile_reviewed_by_user_id')->nullable()->after('profile_review_note')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('companies', 'profile_reviewed_at')) {
                $table->timestamp('profile_reviewed_at')->nullable()->after('profile_reviewed_by_user_id');
            }
        });

        DB::table('companies')
            ->where('is_association_member', true)
            ->where('status', 'approved')
            ->update(['status' => 'profile_incomplete']);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'profile_reviewed_by_user_id')) {
                $table->dropConstrainedForeignId('profile_reviewed_by_user_id');
            }
            foreach (['profile_submitted_at', 'profile_review_note', 'profile_reviewed_at'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

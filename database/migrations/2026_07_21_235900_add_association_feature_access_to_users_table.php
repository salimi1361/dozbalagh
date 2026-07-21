<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'association_feature_keys')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('association_feature_keys')->nullable()->after('is_manual');
            });
        }

        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'association_feature_keys')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('association_feature_keys');
            });
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

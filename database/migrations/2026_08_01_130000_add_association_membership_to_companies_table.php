<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'is_association_member')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->boolean('is_association_member')->default(false)->index()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'is_association_member')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropIndex(['is_association_member']);
                $table->dropColumn('is_association_member');
            });
        }
    }
};

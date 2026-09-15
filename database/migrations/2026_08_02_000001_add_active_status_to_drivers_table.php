<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('drivers', 'is_active')) {
            Schema::table('drivers', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('contract_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('drivers', 'is_active')) {
            Schema::table('drivers', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اضافه کردن ستون شرکت به جدول رانندگان
        if (!Schema::hasColumn('drivers', 'company_id')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            });
        }

        // اضافه کردن ستون شرکت به جدول ناوگان
        if (!Schema::hasColumn('fleets', 'company_id')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('fleets', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
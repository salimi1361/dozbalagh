<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cargo_document_rules', function (Blueprint $table) {
            // حذف ستون‌های بله/خیر قبلی
            $table->dropColumn(['is_tir_required', 'is_declaration_required']);
            
            // اضافه کردن ستون تنظیمات جامع فیلدها به صورت JSON
            $table->json('fields_config')->nullable()->after('cargo_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargo_document_rules', function (Blueprint $table) {
            $table->dropColumn('fields_config');
            $table->boolean('is_tir_required')->default(true);
            $table->boolean('is_declaration_required')->default(true);
        });
    }
};
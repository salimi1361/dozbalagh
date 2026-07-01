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
        Schema::create('cargo_document_rules', function (Blueprint $table) {
            $table->id();
            
            // نوع بار: صادرات، واردات، ترانزیت (می‌توانی از کلمات انگلیسی یا فارسی به عنوان کلید استفاده کنی)
            $table->string('cargo_type')->unique(); 
            
            // قوانین مدارک: این فیلدها مشخص می‌کنند که ادمین برای این نوع بار، چه مدارکی را اجباری یا مخفی می‌خواهد
            $table->boolean('is_tir_required')->default(true); // کارنه تیر اجباری است؟
            $table->boolean('is_declaration_required')->default(true); // اظهارنامه اجباری است؟
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargo_document_rules');
    }
};
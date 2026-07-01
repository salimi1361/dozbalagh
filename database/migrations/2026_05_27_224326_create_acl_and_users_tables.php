<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. جدول نقش‌ها (ادمین، انجمن، شرکت، راننده)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin, association, company, driver
            $table->string('title_fa'); // نام فارسی
            $table->foreignId('parent_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });

        // ۲. جدول مدیریت پویا منوها و اکشن‌ها
        Schema::create('permissions_and_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('permissions_and_menus')->onDelete('cascade');
            $table->string('title_fa');
            $table->string('title_en'); // فینگلیش یا انگلیسی برای چاپ و اپراتور
            $table->string('route_name')->nullable();
            $table->string('icon')->nullable();
            $table->enum('action', ['view', 'create', 'update', 'delete', 'execute'])->default('view');
            $table->timestamps();
        });

        // ۳. جدول واسط ماتریس دسترسی نقش‌ها به منوها
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions_and_menus')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // ۴. جدول اصلی کاربران سیستم دوزبلاغ (متصل به پرگار)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles');
            $table->string('username')->unique(); // کد ملی یا شناسه پرگار
            $table->string('password')->nullable(); // برای ورودهای اضطراری یا ادمن
            $table->string('mobile')->nullable(); // برای اطلاع‌رسانی SMS
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_manual')->default(false); // آیا از پرگار آمده یا دستی است؟
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions_and_menus');
        Schema::dropIfExists('roles');
    }
};
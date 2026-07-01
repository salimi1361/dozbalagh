<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام کشور مثل: ترکیه');
            $table->string('code')->nullable()->comment('کد اختصاری مثل: TR');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال بودن برای صدور دوزبلاغ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_quotas', function (Blueprint $table) {
            $table->id();
            
            // ارتباط با شرکت (یا انجمن)
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade'); 
            
            // ارتباط با کشور
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            
            // حداکثر سهمیه مجاز (مثلا 2 عدد)
            $table->integer('max_limit')->default(0);
            
            // تعداد مصرف شده تا الان
            $table->integer('used_count')->default(0);
            
            $table->timestamps();

            // جلوگیری از ثبت سهمیه تکراری برای یک کشور و یک شرکت
            $table->unique(['company_id', 'country_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_quotas');
    }
};

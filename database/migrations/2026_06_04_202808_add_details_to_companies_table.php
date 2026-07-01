<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            
            if (!Schema::hasColumn('companies', 'name')) {
                $table->string('name')->nullable()->comment('نام فارسی شرکت');
            }
            
            if (!Schema::hasColumn('companies', 'name_en')) {
                $table->string('name_en')->nullable()->comment('نام لاتین یا فینگلیش');
            }
            
            if (!Schema::hasColumn('companies', 'national_id')) {
                $table->string('national_id')->unique()->nullable()->comment('شناسه ملی شرکت');
            }
            
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->comment('تلفن ثابت دفتر');
            }
            
            if (!Schema::hasColumn('companies', 'ceo_mobile')) {
                $table->string('ceo_mobile')->nullable()->comment('شماره موبایل مدیرعامل');
            }
            
            if (!Schema::hasColumn('companies', 'address_fa')) {
                $table->text('address_fa')->nullable()->comment('آدرس کامل فارسی');
            }
            
            if (!Schema::hasColumn('companies', 'address_en')) {
                $table->text('address_en')->nullable()->comment('آدرس کامل لاتین');
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = ['name', 'name_en', 'national_id', 'phone', 'ceo_mobile', 'address_fa', 'address_en'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
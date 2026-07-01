<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // چک می‌کنیم اگر ستون وجود نداشت، آن را بسازد تا ارور ندهد
            if (!Schema::hasColumn('companies', 'ceo_name')) {
                $table->string('ceo_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'ceo_mobile')) {
                $table->string('ceo_mobile')->nullable()->after('ceo_name');
            }
            if (!Schema::hasColumn('companies', 'name_en')) {
                $table->string('name_en')->nullable();
            }
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('companies', 'address_en')) {
                $table->text('address_en')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['ceo_name', 'ceo_mobile', 'name_en', 'phone', 'address_en']);
        });
    }
};
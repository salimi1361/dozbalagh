<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'ceo_name')) {
                $table->string('ceo_name')->nullable();
            }
            if (!Schema::hasColumn('companies', 'address_en')) {
                $table->string('address_en')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['ceo_name', 'address_en']);
        });
    }
};
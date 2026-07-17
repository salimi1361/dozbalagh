<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cmr_serial_pools', function (Blueprint $table) {
            $table->string('suffix', 30)->nullable()->after('prefix');
            $table->unsignedTinyInteger('number_padding')->default(1)->after('suffix');
        });
    }

    public function down(): void
    {
        Schema::table('cmr_serial_pools', function (Blueprint $table) {
            $table->dropColumn(['suffix', 'number_padding']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE UNIQUE INDEX drivers_national_code_unique ON drivers (national_code(32))');

            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->unique('national_code', 'drivers_national_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique('drivers_national_code_unique');
        });
    }
};

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
    Schema::table('permit_requests', function (Blueprint $table) {
        // افزودن ستون سریال در صورتی که واقعاً وجود نداشته باشد
        if (!Schema::hasColumn('permit_requests', 'serial_number')) {
            $table->string('serial_number', 100)->nullable()->after('status')->comment('سریال دوزبلاغ چاپی');
        }
    });
}

public function down()
{
    Schema::table('permit_requests', function (Blueprint $table) {
        $table->dropColumn('serial_number');
    });
}
};

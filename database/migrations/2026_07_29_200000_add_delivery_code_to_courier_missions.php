<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_courier_missions', function (Blueprint $table) {
            $table->string('delivery_code', 20)->nullable()->after('otp_hash');
        });
    }

    public function down(): void
    {
        Schema::table('permit_courier_missions', function (Blueprint $table) {
            $table->dropColumn('delivery_code');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            $table->string('collected_image')->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            $table->dropColumn('collected_image');
        });
    }
};

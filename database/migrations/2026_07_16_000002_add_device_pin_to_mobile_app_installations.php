<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_app_installations', function (Blueprint $table) {
            $table->string('device_pin_hash')->nullable()->after('notifications_enabled');
            $table->timestamp('device_pin_updated_at')->nullable()->after('device_pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_app_installations', function (Blueprint $table) {
            $table->dropColumn(['device_pin_hash', 'device_pin_updated_at']);
        });
    }
};

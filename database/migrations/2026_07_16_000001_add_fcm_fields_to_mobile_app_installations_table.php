<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_app_installations', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('locale');
            $table->timestamp('fcm_token_updated_at')->nullable()->after('fcm_token');
            $table->boolean('notifications_enabled')->default(true)->after('fcm_token_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_app_installations', function (Blueprint $table) {
            $table->dropColumn(['fcm_token', 'fcm_token_updated_at', 'notifications_enabled']);
        });
    }
};

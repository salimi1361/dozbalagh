<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid', 191);
            $table->string('platform', 20);
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('device_name')->nullable();
            $table->string('os_version')->nullable();
            $table->unsignedSmallInteger('sdk_version')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('app_build', 50)->nullable();
            $table->string('app_identifier')->nullable();
            $table->string('locale', 20)->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->timestamp('installed_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['driver_id', 'device_uuid'], 'mobile_app_driver_device_unique');
            $table->index(['platform', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_installations');
    }
};

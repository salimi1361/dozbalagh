<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pwa_installations', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 30);
            $table->unsignedBigInteger('actor_id');
            $table->string('device_uuid', 100);
            $table->string('role', 30);
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->text('user_agent')->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->boolean('is_installed')->default(false);
            $table->boolean('is_standalone')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['actor_type', 'actor_id', 'device_uuid'], 'pwa_actor_device_unique');
            $table->index(['role', 'is_installed']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_installations');
    }
};

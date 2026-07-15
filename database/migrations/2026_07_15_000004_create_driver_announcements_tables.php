<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_role', 30);
            $table->string('title');
            $table->text('message');
            $table->string('priority', 20)->default('normal');
            $table->string('display_mode', 20)->default('normal');
            $table->string('audience_type', 30)->default('all');
            $table->boolean('show_once')->default(true);
            $table->boolean('requires_acknowledgement')->default(false);
            $table->string('acknowledgement_text')->default('مطالعه کردم');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('driver_announcement_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('driver_announcements')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('device_uuid', 191)->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'driver_id']);
            $table->index(['driver_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_announcement_receipts');
        Schema::dropIfExists('driver_announcements');
    }
};

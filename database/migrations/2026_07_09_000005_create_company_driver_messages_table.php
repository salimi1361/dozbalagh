<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_driver_messages')) {
            return;
        }

        Schema::create('company_driver_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('sender', 20);
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('category', 50)->default('general');
            $table->string('priority', 30)->default('normal');
            $table->boolean('requires_acknowledgement')->default(false);
            $table->uuid('notification_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'driver_id', 'created_at']);
            $table->index(['company_id', 'sender', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_driver_messages');
    }
};

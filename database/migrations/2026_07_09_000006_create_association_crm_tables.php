<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_company_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience')->default('selected');
            $table->string('title');
            $table->text('body');
            $table->string('category')->default('general');
            $table->string('priority')->default('normal');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('association_message_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('association_company_messages')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgement_note')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'company_id']);
        });

        Schema::create('association_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('association_company_messages')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('general');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->text('admin_response')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_support_tickets');
        Schema::dropIfExists('association_message_receipts');
        Schema::dropIfExists('association_company_messages');
    }
};

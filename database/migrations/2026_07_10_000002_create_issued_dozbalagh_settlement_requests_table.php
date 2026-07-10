<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_dozbalagh_settlement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('period_key', 7)->unique();
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('issued_count');
            $table->decimal('requested_amount', 15, 2);
            $table->enum('status', ['requested', 'paid', 'rejected'])->default('requested')->index();
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->timestamp('requested_at');
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('receipt_file')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_dozbalagh_settlement_requests');
    }
};

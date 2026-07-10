<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may leave this table behind when a previous CREATE/ALTER fails.
        // Since the migration is not recorded in that state, the table is incomplete.
        Schema::dropIfExists('issued_dozbalagh_settlement_requests');

        Schema::create('issued_dozbalagh_settlement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('period_key', 7)->unique();
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('issued_count');
            $table->decimal('requested_amount', 15, 2);
            $table->enum('status', ['requested', 'paid', 'rejected'])->default('requested')->index();
            $table->unsignedBigInteger('requested_by_user_id');
            $table->timestamp('requested_at');
            $table->unsignedBigInteger('paid_by_user_id')->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('receipt_file')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('requested_by_user_id', 'idsr_requested_user_fk')
                ->references('id')->on('users');
            $table->foreign('paid_by_user_id', 'idsr_paid_user_fk')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_dozbalagh_settlement_requests');
    }
};

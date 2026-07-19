<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shahbaz_license_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('tracking_code', 30)->unique();
            $table->string('request_type', 40);
            $table->string('activity_scope', 50)->nullable();
            $table->string('activity_type', 100)->nullable();
            $table->string('status', 40)->default('draft');
            $table->string('previous_license_number', 100)->nullable();
            $table->date('previous_license_expires_on')->nullable();
            $table->text('company_description')->nullable();
            $table->text('correction_reason')->nullable();
            $table->foreignId('created_by_user_id')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id', 'shbz_lr_company_fk')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'shbz_lr_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'status'], 'shbz_lr_company_status_idx');
        });

        Schema::create('shahbaz_license_request_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id');
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('description')->nullable();
            $table->json('request_snapshot')->nullable();
            $table->foreignId('changed_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('request_id', 'shbz_lrh_request_fk')->references('id')->on('shahbaz_license_requests')->cascadeOnDelete();
            $table->foreign('changed_by_user_id', 'shbz_lrh_changed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['request_id', 'created_at'], 'shbz_lrh_request_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_license_request_histories');
        Schema::dropIfExists('shahbaz_license_requests');
    }
};

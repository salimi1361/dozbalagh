<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shahbaz_misc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('subject', 200);
            $table->text('description')->nullable();
            $table->string('storage_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignId('uploaded_by_user_id')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id', 'shbz_misc_company_fk')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('uploaded_by_user_id', 'shbz_misc_uploaded_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('archived_by_user_id', 'shbz_misc_archived_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'status'], 'shbz_misc_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_misc_documents');
    }
};

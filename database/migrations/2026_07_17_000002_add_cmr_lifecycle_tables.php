<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cmr_documents', 'verification_code')) {
            Schema::table('cmr_documents', function (Blueprint $table) {
                $table->string('verification_code', 64)->nullable()->unique('cmr_docs_verify_uq')->after('integrity_hash');
            });
        }
        if (! Schema::hasColumn('cmr_documents', 'finalized_at')) {
            Schema::table('cmr_documents', function (Blueprint $table) {
                $table->dateTime('finalized_at')->nullable()->after('issued_at');
            });
        }

        if (! Schema::hasTable('cmr_amendments')) {
            Schema::create('cmr_amendments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
                $table->unsignedInteger('from_version');
                $table->unsignedInteger('to_version');
                $table->string('status')->default('applied')->index();
                $table->text('reason');
                $table->json('changes');
                $table->string('previous_hash', 64)->nullable();
                $table->string('new_hash', 64);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['cmr_document_id', 'to_version'], 'cmr_amend_doc_ver_uq');
            });
        }

        if (! Schema::hasTable('cmr_attachments')) {
            Schema::create('cmr_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
                $table->string('document_type');
                $table->string('original_name');
                $table->string('storage_path');
                $table->string('mime_type', 150);
                $table->unsignedBigInteger('size_bytes');
                $table->string('sha256', 64);
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('uploaded_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cmr_signatures')) {
            Schema::create('cmr_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
                $table->unsignedInteger('document_version');
                $table->string('signer_role')->index();
                $table->string('signer_name');
                $table->string('signer_identifier')->nullable();
                $table->string('signature_type')->default('electronic_acknowledgement');
                $table->text('reservation')->nullable();
                $table->string('document_hash', 64);
                $table->string('evidence_hash', 64);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('signed_at');
                $table->timestamps();
                $table->unique(['cmr_document_id', 'document_version', 'signer_role'], 'cmr_sig_doc_ver_role_uq');
            });
        } else {
            Schema::table('cmr_signatures', function (Blueprint $table) {
                $table->unique(['cmr_document_id', 'document_version', 'signer_role'], 'cmr_sig_doc_ver_role_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cmr_signatures');
        Schema::dropIfExists('cmr_attachments');
        Schema::dropIfExists('cmr_amendments');
        if (Schema::hasColumn('cmr_documents', 'verification_code')) {
            Schema::table('cmr_documents', fn (Blueprint $table) => $table->dropColumn('verification_code'));
        }
        if (Schema::hasColumn('cmr_documents', 'finalized_at')) {
            Schema::table('cmr_documents', fn (Blueprint $table) => $table->dropColumn('finalized_at'));
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cmr_company_settings', function (Blueprint $table) {
            $table->boolean('origin_evidence_enabled')->default(false);
            $table->boolean('origin_require_signature')->default(false);
            $table->boolean('origin_require_photo')->default(false);
            $table->boolean('origin_require_gps')->default(false);
            $table->boolean('destination_evidence_enabled')->default(false);
            $table->boolean('destination_require_signature')->default(false);
            $table->boolean('destination_require_photo')->default(false);
            $table->boolean('destination_require_gps')->default(false);
            $table->boolean('allow_delivery_exceptions')->default(true);
        });
        Schema::create('cmr_handover_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
            $table->unsignedInteger('document_version');
            $table->string('stage', 20)->index();
            $table->string('outcome', 40)->index();
            $table->string('signer_name')->nullable();
            $table->string('signer_identifier')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signature_sha256', 64)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('client_uuid')->nullable();
            $table->string('evidence_hash', 64);
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->unique(['cmr_document_id','stage','client_uuid'], 'cmr_hand_doc_stage_client_uq');
        });
        Schema::table('cmr_attachments', function (Blueprint $table) {
            $table->foreignId('cmr_handover_record_id')->nullable()->constrained('cmr_handover_records')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('cmr_attachments', fn (Blueprint $table) => $table->dropConstrainedForeignId('cmr_handover_record_id'));
        Schema::dropIfExists('cmr_handover_records');
        Schema::table('cmr_company_settings', fn (Blueprint $table) => $table->dropColumn([
            'origin_evidence_enabled','origin_require_signature','origin_require_photo','origin_require_gps',
            'destination_evidence_enabled','destination_require_signature','destination_require_photo','destination_require_gps','allow_delivery_exceptions',
        ]));
    }
};

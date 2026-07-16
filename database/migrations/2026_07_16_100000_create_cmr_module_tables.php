<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmr_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('issuance_fee', 15, 2)->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->boolean('billing_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('cmr_tariff_history', function (Blueprint $table) {
            $table->id();
            $table->decimal('issuance_fee', 15, 2);
            $table->string('currency', 3)->default('IRR');
            $table->boolean('billing_enabled');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('effective_from');
            $table->timestamps();
        });

        Schema::create('cmr_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->nullable()->unique();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fleet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('permit_request_id')->nullable()->constrained('permit_requests')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->string('language', 10)->default('en');
            $table->string('transport_type')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('issuance_fee', 15, 2)->default(0);
            $table->string('currency', 3)->default('IRR');
            $table->string('integrity_hash', 64)->nullable();
            $table->string('consignor_name');
            $table->string('consignor_identifier')->nullable();
            $table->text('consignor_address')->nullable();
            $table->string('consignor_country_code', 2)->nullable();
            $table->string('consignee_name');
            $table->string('consignee_identifier')->nullable();
            $table->text('consignee_address')->nullable();
            $table->string('consignee_country_code', 2)->nullable();
            $table->string('carrier_name');
            $table->string('carrier_identifier')->nullable();
            $table->text('carrier_address')->nullable();
            $table->string('carrier_country_code', 2)->nullable();
            $table->string('taking_over_place');
            $table->dateTime('taking_over_at')->nullable();
            $table->string('delivery_place');
            $table->dateTime('planned_delivery_at')->nullable();
            $table->text('sender_instructions')->nullable();
            $table->text('special_agreements')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cmr_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('description');
            $table->string('marks_and_numbers')->nullable();
            $table->string('package_type')->nullable();
            $table->decimal('package_count', 15, 3)->nullable();
            $table->decimal('gross_weight_kg', 15, 3)->nullable();
            $table->decimal('volume_m3', 15, 3)->nullable();
            $table->string('commodity_code')->nullable();
            $table->string('un_number')->nullable();
            $table->string('adr_class')->nullable();
            $table->text('handling_instructions')->nullable();
            $table->timestamps();
            $table->unique(['cmr_document_id', 'line_number']);
        });

        Schema::create('cmr_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('integrity_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->unique(['cmr_document_id', 'version']);
        });

        Schema::create('cmr_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('actor_role')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
        });

        Schema::create('cmr_wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('cmr_document_id')->constrained('cmr_documents')->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IRR');
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmr_wallet_entries');
        Schema::dropIfExists('cmr_events');
        Schema::dropIfExists('cmr_versions');
        Schema::dropIfExists('cmr_goods');
        Schema::dropIfExists('cmr_documents');
        Schema::dropIfExists('cmr_tariff_history');
        Schema::dropIfExists('cmr_settings');
    }
};

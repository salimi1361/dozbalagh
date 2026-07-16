<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmr_company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('assignment_policy')->default('same_company');
            $table->string('serial_mode')->default('system');
            $table->string('print_language', 10)->default('en');
            $table->boolean('require_latin_data')->default(true);
            $table->timestamps();
        });

        Schema::create('cmr_serial_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->unsignedBigInteger('range_start');
            $table->unsignedBigInteger('range_end');
            $table->unsignedBigInteger('next_number');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cmr_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serial_pool_id')->nullable()->constrained('cmr_serial_pools')->nullOnDelete();
            $table->string('serial');
            $table->string('status')->default('available')->index();
            $table->foreignId('cmr_document_id')->nullable()->unique()->constrained('cmr_documents')->nullOnDelete();
            $table->dateTime('reserved_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'serial']);
        });

        Schema::create('cmr_print_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('print_mode')->default('full');
            $table->string('header_mode')->default('company_profile');
            $table->string('paper_size')->default('A4');
            $table->string('orientation')->default('portrait');
            $table->string('logo_path')->nullable();
            $table->string('background_path')->nullable();
            $table->string('custom_company_name')->nullable();
            $table->text('custom_company_address')->nullable();
            $table->string('custom_company_contact')->nullable();
            $table->json('field_layout')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('cmr_documents', function (Blueprint $table) {
            $table->string('company_serial')->nullable()->after('number');
            $table->foreignId('print_template_id')->nullable()->after('permit_request_id')->constrained('cmr_print_templates')->nullOnDelete();
            $table->json('attached_documents')->nullable()->after('special_agreements');
            $table->json('successive_carriers')->nullable()->after('attached_documents');
            $table->text('carrier_reservations')->nullable()->after('successive_carriers');
            $table->string('carriage_payment')->nullable()->after('carrier_reservations');
            $table->decimal('cash_on_delivery', 15, 2)->nullable()->after('carriage_payment');
            $table->json('charges')->nullable()->after('cash_on_delivery');
            $table->string('established_at_place')->nullable()->after('charges');
            $table->date('established_at_date')->nullable()->after('established_at_place');
            $table->unique(['company_id', 'company_serial']);
        });
    }

    public function down(): void
    {
        Schema::table('cmr_documents', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'company_serial']);
            $table->dropConstrainedForeignId('print_template_id');
            $table->dropColumn([
                'company_serial', 'attached_documents', 'successive_carriers',
                'carrier_reservations', 'carriage_payment', 'cash_on_delivery',
                'charges', 'established_at_place', 'established_at_date',
            ]);
        });
        Schema::dropIfExists('cmr_print_templates');
        Schema::dropIfExists('cmr_serials');
        Schema::dropIfExists('cmr_serial_pools');
        Schema::dropIfExists('cmr_company_settings');
    }
};

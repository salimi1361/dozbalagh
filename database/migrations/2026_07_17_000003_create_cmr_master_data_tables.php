<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmr_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('party_type')->index();
            $table->string('legal_name');
            $table->string('identifier')->nullable();
            $table->text('address')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'party_type', 'legal_name'], 'cmr_party_company_type_name_uq');
        });

        Schema::create('cmr_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('location_type')->index();
            $table->string('name');
            $table->string('country_code', 2)->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'location_type', 'name'], 'cmr_location_company_type_name_uq');
        });

        Schema::create('cmr_goods_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description');
            $table->string('package_type')->nullable();
            $table->string('commodity_code')->nullable();
            $table->string('un_number')->nullable();
            $table->string('adr_class')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name'], 'cmr_goods_company_name_uq');
        });

        DB::table('cmr_company_settings')->update(['serial_mode' => 'pool']);
    }

    public function down(): void
    {
        Schema::dropIfExists('cmr_goods_templates');
        Schema::dropIfExists('cmr_locations');
        Schema::dropIfExists('cmr_parties');
    }
};

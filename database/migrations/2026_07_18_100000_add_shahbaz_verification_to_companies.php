<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('registration_number')->nullable();
            $table->string('ceo_national_code', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('shahbaz_verification_status')->default('profile_incomplete')->index();
            $table->foreignId('shahbaz_verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('shahbaz_verified_at')->nullable();
            $table->text('shahbaz_review_note')->nullable();
            $table->string('activity_license_number')->nullable()->unique();
            $table->date('activity_license_issued_on')->nullable();
            $table->date('activity_license_expires_on')->nullable()->index();
            $table->string('activity_license_status')->default('unverified')->index();
        });

        Schema::create('shahbaz_company_verification_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('result')->nullable();
            $table->text('description')->nullable();
            $table->json('company_snapshot')->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_company_verification_histories');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shahbaz_verified_by_user_id');
            $table->dropUnique(['activity_license_number']);
            $table->dropIndex(['shahbaz_verification_status']);
            $table->dropIndex(['activity_license_expires_on']);
            $table->dropIndex(['activity_license_status']);
            $table->dropColumn([
                'registration_number', 'ceo_national_code', 'postal_code', 'province', 'city',
                'activity_type', 'shahbaz_verification_status', 'shahbaz_verified_at',
                'shahbaz_review_note', 'activity_license_number', 'activity_license_issued_on',
                'activity_license_expires_on', 'activity_license_status',
            ]);
        });
    }
};

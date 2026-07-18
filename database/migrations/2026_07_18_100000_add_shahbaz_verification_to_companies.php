<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $add = static function (string $column, callable $definition): void {
            if (! Schema::hasColumn('companies', $column)) {
                Schema::table('companies', $definition);
            }
        };

        $add('registration_number', fn (Blueprint $table) => $table->string('registration_number')->nullable());
        $add('ceo_national_code', fn (Blueprint $table) => $table->string('ceo_national_code', 20)->nullable());
        $add('postal_code', fn (Blueprint $table) => $table->string('postal_code', 20)->nullable());
        $add('province', fn (Blueprint $table) => $table->string('province')->nullable());
        $add('city', fn (Blueprint $table) => $table->string('city')->nullable());
        $add('activity_type', fn (Blueprint $table) => $table->string('activity_type')->nullable());
        $add('shahbaz_verification_status', fn (Blueprint $table) => $table->string('shahbaz_verification_status')->default('profile_incomplete')->index());
        $add('shahbaz_verified_by_user_id', function (Blueprint $table) {
            $table->foreignId('shahbaz_verified_by_user_id')->nullable();
            $table->foreign('shahbaz_verified_by_user_id', 'shbz_company_verified_by_fk')->references('id')->on('users')->nullOnDelete();
        });
        $add('shahbaz_verified_at', fn (Blueprint $table) => $table->dateTime('shahbaz_verified_at')->nullable());
        $add('shahbaz_review_note', fn (Blueprint $table) => $table->text('shahbaz_review_note')->nullable());
        $add('activity_license_number', fn (Blueprint $table) => $table->string('activity_license_number')->nullable()->unique());
        $add('activity_license_issued_on', fn (Blueprint $table) => $table->date('activity_license_issued_on')->nullable());
        $add('activity_license_expires_on', fn (Blueprint $table) => $table->date('activity_license_expires_on')->nullable()->index());
        $add('activity_license_status', fn (Blueprint $table) => $table->string('activity_license_status')->default('unverified')->index());

        if (! Schema::hasTable('shahbaz_company_verification_histories')) {
            Schema::create('shahbaz_company_verification_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->string('result')->nullable();
                $table->text('description')->nullable();
                $table->json('company_snapshot')->nullable();
                $table->foreignId('changed_by_user_id')->nullable();
                $table->dateTime('checked_at')->nullable();
                $table->timestamps();
                $table->foreign('company_id', 'shbz_ver_company_fk')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('changed_by_user_id', 'shbz_ver_changed_by_fk')->references('id')->on('users')->nullOnDelete();
                $table->index(['company_id', 'created_at'], 'shbz_ver_company_created_idx');
            });
        } else {
            // MySQL may leave the table behind when the original long FK name fails.
            Schema::table('shahbaz_company_verification_histories', function (Blueprint $table) {
                $table->foreign('changed_by_user_id', 'shbz_ver_changed_by_fk')->references('id')->on('users')->nullOnDelete();
            });
        }
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

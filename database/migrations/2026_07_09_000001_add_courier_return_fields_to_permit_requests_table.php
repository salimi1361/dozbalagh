<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            $table->string('company_return_image')->nullable()->after('collected_image');
            $table->string('courier_name')->nullable()->after('company_return_image');
            $table->string('courier_mobile', 30)->nullable()->after('courier_name');
            $table->string('courier_national_code', 30)->nullable()->after('courier_mobile');
            $table->string('courier_vehicle_plate')->nullable()->after('courier_national_code');
            $table->string('courier_delivery_code', 20)->nullable()->after('courier_vehicle_plate');
            $table->timestamp('courier_code_sent_at')->nullable()->after('courier_delivery_code');
            $table->timestamp('company_return_submitted_at')->nullable()->after('courier_code_sent_at');
            $table->timestamp('courier_received_at')->nullable()->after('company_return_submitted_at');
            $table->unsignedBigInteger('courier_received_by_user_id')->nullable()->after('courier_received_at');
            $table->timestamp('lost_reported_at')->nullable()->after('courier_received_by_user_id');
            $table->text('lost_reason')->nullable()->after('lost_reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('permit_requests', function (Blueprint $table) {
            $table->dropColumn([
                'company_return_image',
                'courier_name',
                'courier_mobile',
                'courier_national_code',
                'courier_vehicle_plate',
                'courier_delivery_code',
                'courier_code_sent_at',
                'company_return_submitted_at',
                'courier_received_at',
                'courier_received_by_user_id',
                'lost_reported_at',
                'lost_reason',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'company_return_image' => fn (Blueprint $table) => $table->string('company_return_image')->nullable(),
            'courier_name' => fn (Blueprint $table) => $table->string('courier_name')->nullable(),
            'courier_mobile' => fn (Blueprint $table) => $table->string('courier_mobile', 30)->nullable(),
            'courier_national_code' => fn (Blueprint $table) => $table->string('courier_national_code', 30)->nullable(),
            'courier_vehicle_plate' => fn (Blueprint $table) => $table->string('courier_vehicle_plate', 100)->nullable(),
            'courier_delivery_code' => fn (Blueprint $table) => $table->string('courier_delivery_code', 20)->nullable(),
            'courier_code_sent_at' => fn (Blueprint $table) => $table->timestamp('courier_code_sent_at')->nullable(),
            'company_return_submitted_at' => fn (Blueprint $table) => $table->timestamp('company_return_submitted_at')->nullable(),
            'courier_received_at' => fn (Blueprint $table) => $table->timestamp('courier_received_at')->nullable(),
            'courier_received_by_user_id' => fn (Blueprint $table) => $table->unsignedBigInteger('courier_received_by_user_id')->nullable(),
            'collected_image' => fn (Blueprint $table) => $table->string('collected_image')->nullable(),
            'lost_reported_at' => fn (Blueprint $table) => $table->timestamp('lost_reported_at')->nullable(),
            'lost_reason' => fn (Blueprint $table) => $table->text('lost_reason')->nullable(),
            'closed_at' => fn (Blueprint $table) => $table->timestamp('closed_at')->nullable(),
        ];

        foreach ($columns as $name => $definition) {
            if (!Schema::hasColumn('permit_request_items', $name)) {
                Schema::table('permit_request_items', $definition);
            }
        }

        DB::table('permit_request_items')
            ->where('return_status', 'company_returned')
            ->orderBy('id')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    $meta = json_decode((string) $item->rejection_reason, true);
                    $meta = is_array($meta) ? $meta : [];
                    DB::table('permit_request_items')->where('id', $item->id)->update([
                        'item_status' => 'company_returned',
                        'disposition_type' => $item->disposition_type ?: 'return_archive',
                        'disposition_note' => $item->disposition_note ?: 'انتقال خودکار از ساختار پشتیبان لاشه',
                        'company_return_image' => $item->company_return_image ?: $item->return_cmr_file,
                        'courier_name' => $item->courier_name ?: ($meta['cn'] ?? null),
                        'courier_mobile' => $item->courier_mobile ?: ($meta['cm'] ?? null),
                        'courier_delivery_code' => $item->courier_delivery_code ?: ($meta['dc'] ?? null),
                        'company_return_submitted_at' => $item->company_return_submitted_at ?: $item->updated_at,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // مهاجرت ترمیمی و داده‌محور است؛ برای جلوگیری از حذف سوابق عمداً برگشت مخرب ندارد.
    }
};

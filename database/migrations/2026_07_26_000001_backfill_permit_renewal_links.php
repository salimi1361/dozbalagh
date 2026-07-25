<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('permit_requests')
            || ! Schema::hasTable('permit_request_items')
            || ! Schema::hasColumn('permit_requests', 'request_type')
            || ! Schema::hasColumn('permit_request_items', 'renewed_from_item_id')
        ) {
            return;
        }

        DB::table('permit_request_items as renewal_item')
            ->join('permit_requests as renewal_request', 'renewal_request.id', '=', 'renewal_item.permit_request_id')
            ->whereNotNull('renewal_item.renewed_from_item_id')
            ->select([
                'renewal_request.id as renewal_request_id',
                'renewal_item.renewed_from_item_id',
            ])
            ->orderBy('renewal_request.id')
            ->each(function ($renewal) {
                $source = DB::table('permit_request_items as source_item')
                    ->join('permit_requests as source_request', 'source_request.id', '=', 'source_item.permit_request_id')
                    ->where('source_item.id', $renewal->renewed_from_item_id)
                    ->select([
                        'source_request.id',
                        'source_request.d_code',
                        'source_item.d_serial_number',
                    ])
                    ->first();

                if (! $source) {
                    return;
                }

                DB::table('permit_requests')
                    ->where('id', $renewal->renewal_request_id)
                    ->update([
                        'request_type' => 'renewal',
                        'previous_request_id' => $source->id,
                        'previous_d_code' => $source->d_code,
                        'previous_serial_number' => $source->d_serial_number,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // این مهاجرت فقط داده‌های ارتباطی ازدست‌رفته را بازیابی می‌کند.
        // پاک‌کردن این ارتباط‌ها در rollback باعث تخریب تاریخچه تمدید می‌شود.
    }
};

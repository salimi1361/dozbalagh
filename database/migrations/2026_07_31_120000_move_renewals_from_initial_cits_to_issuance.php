<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permit_request_items')
            ->where('item_status', 'approved')
            ->whereIn('permit_request_id', DB::table('permit_requests')
                ->where('request_type', 'renewal')
                ->select('id'))
            ->update([
                'item_status' => 'ready_to_issue',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // This data correction is intentionally irreversible.
    }
};

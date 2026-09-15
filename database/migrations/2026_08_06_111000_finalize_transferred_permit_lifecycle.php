<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_item_transfers', function (Blueprint $table) {
            $table->foreignId('source_permit_request_id')->nullable()->after('permit_request_item_id')->constrained('permit_requests')->nullOnDelete();
            $table->foreignId('destination_permit_request_id')->nullable()->after('source_permit_request_id')->constrained('permit_requests')->nullOnDelete();
        });

        DB::table('permit_item_transfers')->update([
            'destination_permit_request_id' => DB::raw('(SELECT permit_request_id FROM permit_request_items WHERE permit_request_items.id = permit_item_transfers.permit_request_item_id)'),
        ]);
        DB::table('permit_request_items')
            ->whereIn('id', DB::table('permit_item_transfers')->select('permit_request_item_id'))
            ->update(['item_status' => 'transferred_in']);
    }

    public function down(): void
    {
        DB::table('permit_request_items')
            ->whereIn('id', DB::table('permit_item_transfers')->select('permit_request_item_id'))
            ->where('item_status', 'transferred_in')
            ->update(['item_status' => 'issued']);

        Schema::table('permit_item_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_permit_request_id');
            $table->dropConstrainedForeignId('source_permit_request_id');
        });
    }
};

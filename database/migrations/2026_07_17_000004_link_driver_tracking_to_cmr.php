<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->foreignId('cmr_document_id')->nullable()->after('dozbalagh_item_id')->constrained('cmr_documents')->cascadeOnDelete();
            $table->foreignId('dozbalagh_item_id')->nullable()->change();
            $table->index(['cmr_document_id','recorded_at'], 'driver_locations_cmr_recorded_idx');
        });
        Schema::table('driver_events', function (Blueprint $table) {
            $table->foreignId('cmr_document_id')->nullable()->after('dozbalagh_item_id')->constrained('cmr_documents')->cascadeOnDelete();
            $table->foreignId('dozbalagh_item_id')->nullable()->change();
            $table->index(['cmr_document_id','created_at'], 'driver_events_cmr_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('driver_events', function (Blueprint $table) { $table->dropIndex('driver_events_cmr_created_idx'); $table->dropConstrainedForeignId('cmr_document_id'); });
        Schema::table('driver_locations', function (Blueprint $table) { $table->dropIndex('driver_locations_cmr_recorded_idx'); $table->dropConstrainedForeignId('cmr_document_id'); });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dozbalagh_batches', 'permit_type')) {
            Schema::table('dozbalagh_batches', function (Blueprint $table) {
                $table->string('permit_type', 100)->nullable()->after('country_id');
                $table->index(
                    ['country_id', 'permit_type', 'status'],
                    'dozbalagh_batches_country_permit_status_index'
                );
            });
        }

        // Only backfill when the country has exactly one configured permit type.
        // If there are several possible types, guessing would recreate the bug.
        DB::table('dozbalagh_batches')
            ->whereNull('permit_type')
            ->orderBy('id')
            ->get(['id', 'country_id'])
            ->each(function (object $batch): void {
                if (! $batch->country_id) {
                    return;
                }

                $allowedTypes = DB::table('countries')
                    ->where('id', $batch->country_id)
                    ->value('allowed_permit_types');

                $allowedTypes = is_array($allowedTypes)
                    ? $allowedTypes
                    : json_decode((string) $allowedTypes, true);

                if (! is_array($allowedTypes) || count($allowedTypes) !== 1) {
                    return;
                }

                $permitType = trim((string) ($allowedTypes[0] ?? ''));
                if ($permitType === '') {
                    return;
                }

                DB::table('dozbalagh_batches')
                    ->where('id', $batch->id)
                    ->update(['permit_type' => $permitType]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('dozbalagh_batches', 'permit_type')) {
            Schema::table('dozbalagh_batches', function (Blueprint $table) {
                $table->dropIndex('dozbalagh_batches_country_permit_status_index');
                $table->dropColumn('permit_type');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dozbalagh_items', 'country_id')) {
            Schema::table('dozbalagh_items', function (Blueprint $table): void {
                $table->foreignId('country_id')
                    ->nullable()
                    ->after('batch_id')
                    ->constrained('countries')
                    ->nullOnDelete();
                $table->string('permit_type', 100)
                    ->nullable()
                    ->after('country_id');
            });
        }

        // The batch remains the source of truth. Copy its scope to each item so
        // the database can enforce the same business rule as allocation queries.
        DB::statement('
            UPDATE dozbalagh_items AS item
            INNER JOIN dozbalagh_batches AS batch ON batch.id = item.batch_id
            SET item.country_id = batch.country_id,
                item.permit_type = batch.permit_type
        ');

        Schema::table('dozbalagh_items', function (Blueprint $table): void {
            $table->dropUnique('dozbalagh_items_serial_number_unique');
            $table->unique(
                ['country_id', 'permit_type', 'serial_number'],
                'dozbalagh_items_country_permit_serial_unique'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dozbalagh_items', 'country_id')) {
            return;
        }

        Schema::table('dozbalagh_items', function (Blueprint $table): void {
            $table->dropUnique('dozbalagh_items_country_permit_serial_unique');
            $table->unique('serial_number', 'dozbalagh_items_serial_number_unique');
            $table->dropForeign(['country_id']);
            $table->dropColumn(['country_id', 'permit_type']);
        });
    }
};

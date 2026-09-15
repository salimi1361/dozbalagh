<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fleets', 'fleet_loading_type_id')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->foreignId('fleet_loading_type_id')->nullable()->after('truck_type')->constrained('fleet_loading_types')->nullOnDelete();
                $table->string('loading_type_code', 50)->nullable()->after('fleet_loading_type_id');
                $table->string('loading_type_source', 20)->default('manual')->after('loading_type_code');
                $table->json('organization_payload')->nullable()->after('loading_type_source');
                $table->index('loading_type_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fleets', 'fleet_loading_type_id')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->dropForeign(['fleet_loading_type_id']);
                $table->dropIndex(['loading_type_code']);
                $table->dropColumn([
                    'fleet_loading_type_id',
                    'loading_type_code',
                    'loading_type_source',
                    'organization_payload',
                ]);
            });
        }
    }
};

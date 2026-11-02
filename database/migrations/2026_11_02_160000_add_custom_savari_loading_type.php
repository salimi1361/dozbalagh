<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NAME = 'سواری';

    public function up(): void
    {
        if (! Schema::hasTable('fleet_loading_types')) {
            return;
        }

        $exists = DB::table('fleet_loading_types')
            ->where('name_fa', self::NAME)
            ->whereNull('rmto_code')
            ->where('is_international', true)
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('fleet_loading_types')->insert([
            'rmto_code' => null,
            'name_fa' => self::NAME,
            'min_weight_kg' => null,
            'max_weight_kg' => null,
            'vehicle_scope' => 'trailer',
            'is_international' => true,
            'is_active' => true,
            'association_enabled' => true,
            'is_custom' => true,
            'created_by_user_id' => null,
            'sort_order' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fleet_loading_types')) {
            return;
        }

        DB::table('fleet_loading_types')
            ->where('name_fa', self::NAME)
            ->whereNull('rmto_code')
            ->where('is_international', true)
            ->where('is_custom', true)
            ->whereNull('created_by_user_id')
            ->delete();
    }
};

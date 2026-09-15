<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fleet_loading_types')) {
            DB::table('fleet_loading_types')
                ->where('is_custom', false)
                ->where('is_international', true)
                ->update(['association_enabled' => true, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fleet_loading_types')) {
            DB::table('fleet_loading_types')
                ->where('is_custom', false)
                ->where('is_international', true)
                ->update(['association_enabled' => false, 'updated_at' => now()]);
        }
    }
};

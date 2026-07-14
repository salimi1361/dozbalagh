<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('id');
            $table->decimal('accuracy', 8, 2)->nullable()->after('longitude');
            $table->decimal('altitude', 10, 2)->nullable()->after('accuracy');
            $table->decimal('heading', 6, 2)->nullable()->after('speed');
            $table->decimal('speed', 8, 2)->nullable()->change();
            $table->timestamps();
            $table->unique(['driver_id', 'client_uuid'], 'driver_locations_driver_uuid_unique');
            $table->index(['dozbalagh_item_id', 'recorded_at'], 'driver_locations_item_recorded_index');
        });
    }

    public function down(): void
    {
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->dropUnique('driver_locations_driver_uuid_unique');
            $table->dropIndex('driver_locations_item_recorded_index');
            $table->dropColumn(['client_uuid', 'accuracy', 'altitude', 'heading', 'created_at', 'updated_at']);
            $table->integer('speed')->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('countries', 'requestable_permit_types')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->json('requestable_permit_types')
                    ->nullable()
                    ->after('allowed_permit_types');
            });
        }

        DB::table('countries')
            ->select(['id', 'allowed_permit_types', 'requestable_permit_types'])
            ->whereNull('requestable_permit_types')
            ->orderBy('id')
            ->chunkById(100, function ($countries): void {
                foreach ($countries as $country) {
                    $definedTypes = json_decode((string) ($country->allowed_permit_types ?? '[]'), true);

                    DB::table('countries')
                        ->where('id', $country->id)
                        ->update([
                            'requestable_permit_types' => json_encode(
                                is_array($definedTypes) ? array_values($definedTypes) : [],
                                JSON_UNESCAPED_UNICODE,
                            ),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('countries', 'requestable_permit_types')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('requestable_permit_types');
            });
        }
    }
};

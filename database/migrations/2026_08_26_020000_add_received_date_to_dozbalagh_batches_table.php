<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dozbalagh_batches', 'received_date')) {
            Schema::table('dozbalagh_batches', function (Blueprint $table): void {
                $table->date('received_date')->nullable()->after('expiry_date')->index();
            });
        }

        DB::table('dozbalagh_batches')
            ->whereNull('received_date')
            ->update(['received_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('dozbalagh_batches', 'received_date')) {
            Schema::table('dozbalagh_batches', function (Blueprint $table): void {
                $table->dropColumn('received_date');
            });
        }
    }
};

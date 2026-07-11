<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_print_layouts', function (Blueprint $table) {
            $table->string('seal_path')->nullable()->after('background_path');
            $table->string('signature_path')->nullable()->after('seal_path');
        });
    }

    public function down(): void
    {
        Schema::table('permit_print_layouts', function (Blueprint $table) {
            $table->dropColumn(['seal_path', 'signature_path']);
        });
    }
};

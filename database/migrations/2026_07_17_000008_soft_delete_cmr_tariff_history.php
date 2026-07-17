<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('cmr_tariff_history', function(Blueprint $table){$table->softDeletes();$table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();$table->text('deletion_reason')->nullable();}); }
    public function down(): void { Schema::table('cmr_tariff_history', function(Blueprint $table){$table->dropConstrainedForeignId('deleted_by');$table->dropColumn(['deletion_reason','deleted_at']);}); }
};

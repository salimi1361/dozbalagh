<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('cmr_tariff_history', function(Blueprint $table){$table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();$table->text('correction_reason')->nullable();$table->dateTime('corrected_at')->nullable();}); }
    public function down(): void { Schema::table('cmr_tariff_history', function(Blueprint $table){$table->dropConstrainedForeignId('corrected_by');$table->dropColumn(['correction_reason','corrected_at']);}); }
};

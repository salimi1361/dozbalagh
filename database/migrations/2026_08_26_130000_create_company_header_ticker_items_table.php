<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_header_ticker_items', function (Blueprint $table) {
            $table->id();
            $table->string('message', 500);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $raw = DB::table('system_settings')->where('key', 'company_header_ticker')->value('value');
        $legacy = is_string($raw) ? json_decode($raw, true) : null;
        $message = trim((string) ($legacy['message'] ?? ''));

        if ($message !== '') {
            DB::table('company_header_ticker_items')->insert([
                'message' => $message,
                'is_active' => (bool) ($legacy['is_active'] ?? false),
                'sort_order' => 10,
                'created_by' => $legacy['updated_by'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_header_ticker_items');
    }
};

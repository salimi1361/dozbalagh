<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('system_settings')->insert([
            [
                'key' => 'permit_request_window_enabled',
                'value' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permit_request_start_time',
                'value' => '08:00',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permit_request_end_time',
                'value' => '14:00',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permit_request_closed_weekdays',
                'value' => json_encode([5]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'permit_request_closed_message',
                'value' => 'ثبت درخواست فقط در بازه زمانی مجاز انجمن امکان‌پذیر است.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

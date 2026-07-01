<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('world_countries', function (Blueprint $table) {
        $table->id();
        $table->string('iso_code', 2)->unique(); // مثل IR, TR, RU
        $table->string('name_fa'); // نام فارسی: ترکیه
        $table->string('name_en'); // نام انگلیسی: Turkey
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_countries');
    }
};

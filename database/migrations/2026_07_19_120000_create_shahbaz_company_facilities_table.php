<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shahbaz_company_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('location_type', 100);
            $table->string('ownership_type', 50);
            $table->string('postal_code', 20);
            $table->string('phone', 30)->nullable();
            $table->text('address');
            $table->date('started_on')->nullable();
            $table->json('facilities')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_company_facilities');
    }
};

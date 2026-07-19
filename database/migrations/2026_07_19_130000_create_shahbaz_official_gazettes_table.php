<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shahbaz_official_gazettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('gazette_number', 50);
            $table->date('gazette_date');
            $table->string('change_group', 150);
            $table->string('notice_number', 100)->nullable();
            $table->date('notice_date')->nullable();
            $table->string('subject', 255);
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'gazette_number'], 'sh_gazette_company_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_official_gazettes');
    }
};

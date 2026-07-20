<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shahbaz_dossier_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('section_key', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('status', 30);
            $table->text('note')->nullable();
            $table->json('entity_snapshot')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable();
            $table->dateTime('reviewed_at');
            $table->timestamps();

            $table->foreign('company_id', 'shbz_dr_company_fk')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('reviewed_by_user_id', 'shbz_dr_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'section_key'], 'shbz_dr_company_section_idx');
            $table->index(['entity_type', 'entity_id', 'id'], 'shbz_dr_entity_latest_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_dossier_reviews');
    }
};

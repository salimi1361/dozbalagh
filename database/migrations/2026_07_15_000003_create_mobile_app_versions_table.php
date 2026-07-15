<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->unique();
            $table->string('version_name', 50);
            $table->unsignedInteger('latest_build');
            $table->unsignedInteger('minimum_build');
            $table->boolean('force_update')->default(false);
            $table->boolean('maintenance_mode')->default(false);
            $table->string('download_url');
            $table->string('file_checksum', 128)->nullable();
            $table->text('message')->nullable();
            $table->text('release_notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_versions');
    }
};

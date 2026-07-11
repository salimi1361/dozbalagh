<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_print_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('permit_type', 100);
            $table->string('name');
            $table->string('background_path')->nullable();
            $table->decimal('paper_width_mm', 8, 2)->default(210);
            $table->decimal('paper_height_mm', 8, 2)->default(297);
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->decimal('offset_x_mm', 8, 2)->default(0);
            $table->decimal('offset_y_mm', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['country_id', 'permit_type', 'version'], 'permit_print_layout_version_unique');
        });

        Schema::create('permit_print_layout_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained('permit_print_layouts')->cascadeOnDelete();
            $table->string('field_key', 100);
            $table->string('label');
            $table->decimal('x_mm', 8, 2)->default(10);
            $table->decimal('y_mm', 8, 2)->default(10);
            $table->decimal('width_mm', 8, 2)->default(50);
            $table->decimal('height_mm', 8, 2)->default(8);
            $table->decimal('font_size_pt', 6, 2)->default(11);
            $table->string('font_family')->default('Tahoma');
            $table->enum('text_align', ['left', 'center', 'right'])->default('center');
            $table->decimal('rotation_deg', 6, 2)->default(0);
            $table->boolean('is_bold')->default(false);
            $table->boolean('show_on_original')->default(true);
            $table->boolean('show_on_copy')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('permit_print_layout_masks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained('permit_print_layouts')->cascadeOnDelete();
            $table->string('label')->default('پوشاندن شماره نمونه');
            $table->decimal('x_mm', 8, 2)->default(10);
            $table->decimal('y_mm', 8, 2)->default(10);
            $table->decimal('width_mm', 8, 2)->default(40);
            $table->decimal('height_mm', 8, 2)->default(8);
            $table->string('color', 20)->default('#ffffff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_print_layout_masks');
        Schema::dropIfExists('permit_print_layout_fields');
        Schema::dropIfExists('permit_print_layouts');
    }
};

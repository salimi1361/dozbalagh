<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
    {
        Schema::table('dozbalagh_items', function (Blueprint $table) {
            // افزایش طول ستون به 50 کاراکتر تا همه وضعیت‌ها مثل 'in_association' جا شوند
            $table->string('lifecycle_status', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shahbaz_branch_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('branch_type', 50);
            $table->string('name', 200);
            $table->string('province', 100);
            $table->string('city', 100);
            $table->text('address');
            $table->string('manager_name', 200)->nullable();
            $table->string('permit_number', 100)->unique();
            $table->date('issued_on');
            $table->date('expires_on');
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'status'], 'sh_branch_company_status_idx');
            $table->index('expires_on', 'sh_branch_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shahbaz_branch_permits');
    }
};

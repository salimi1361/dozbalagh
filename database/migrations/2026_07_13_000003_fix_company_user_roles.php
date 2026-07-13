<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companyRoleId = DB::table('roles')->where('name', 'company')->value('id');

        if (!$companyRoleId) {
            return;
        }

        $companyUserIds = DB::table('companies')->pluck('user_id');

        if ($companyUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $companyUserIds)
                ->update(['role_id' => $companyRoleId]);
        }
    }

    public function down(): void
    {
        // Previous incorrect roles cannot be reconstructed reliably.
    }
};

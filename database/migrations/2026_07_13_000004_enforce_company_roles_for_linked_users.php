<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companyRoleId = DB::table('roles')->where('name', 'company')->value('id');

        if (! $companyRoleId) {
            throw new \RuntimeException('The company role must exist before company user roles can be repaired.');
        }

        DB::table('users')
            ->whereIn('id', DB::table('companies')->select('user_id'))
            ->where('role_id', '!=', $companyRoleId)
            ->update([
                'role_id' => $companyRoleId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Previous incorrect roles cannot be reconstructed reliably.
    }
};

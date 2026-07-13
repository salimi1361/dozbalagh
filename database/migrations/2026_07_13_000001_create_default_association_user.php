<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $associationRoleId = DB::table('roles')->where('name', 'association')->value('id');

        if (! $associationRoleId) {
            $associationRoleId = DB::table('roles')->insertGetId([
                'name' => 'association',
                'title_fa' => 'انجمن صنفی بین‌المللی',
                'parent_id' => $adminRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')->updateOrInsert(
            ['username' => 'association'],
            [
                'role_id' => $associationRoleId,
                'password' => Hash::make('12345678'),
                'status' => 'active',
                'is_manual' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        // The account may be in active use; rollback must not delete user data.
    }
};

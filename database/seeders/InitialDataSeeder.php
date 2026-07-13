<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // ۱. ساخت نقش مدیریت بدون خطا
        DB::table('roles')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'admin',
                'title_fa' => 'مدیر سیستم',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // ۲. ساخت کاربر ادمین بدون خطا
        DB::table('users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'role_id' => 1,
                'password' => Hash::make('12345678'),
                'mobile' => '09123456789',
                'status' => 'active',
                'is_manual' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        $associationRoleId = DB::table('roles')->where('name', 'association')->value('id');

        if (! $associationRoleId) {
            $associationRoleId = DB::table('roles')->insertGetId([
                'name' => 'association',
                'title_fa' => 'انجمن صنفی بین‌المللی',
                'parent_id' => 1,
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
                'is_manual' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

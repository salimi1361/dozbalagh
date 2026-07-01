<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ۱. تعریف نقش ریشه (Super Admin)
        $adminId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'title_fa' => 'ادمین کل سیستم',
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ۲. تعریف لایه اول زیرمجموعه: انجمن بین‌المللی
        $associationId = DB::table('roles')->insertGetId([
            'name' => 'association',
            'title_fa' => 'انجمن صنفی بین‌المللی',
            'parent_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ۳. تعریف لایه دوم زیرمجموعه: شرکت‌های بین‌المللی (کریر/فورواردر)
        $companyId = DB::table('roles')->insertGetId([
            'name' => 'company',
            'title_fa' => 'شرکت حمل‌و‌نقل بین‌المللی',
            'parent_id' => $associationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ۴. تعریف لایه سوم زیرمجموعه: رانندگان ترانزیت دوزبلاغ
        DB::table('roles')->insert([
            'name' => 'driver',
            'title_fa' => 'راننده ترانزیت ثبت‌شده',
            'parent_id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
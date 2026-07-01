<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMenuController
{
    /**
     * نمایش صفحه مدیریت منوها به ادمین
     */
    public function index()
    {
        // واکشی تمام منوهای موجود در دیتابیس برای نمایش در جدول مدیریت
        $allMenus = DB::table('permissions_and_menus')->get();
        
        // واکشی منوهای مادر جهت استفاده در لیست کشویی فرم (برای ساختار درختی parent_id)
        $parentMenus = DB::table('permissions_and_menus')->whereNull('parent_id')->get();

        return view('admin.menus', compact('allMenus', 'parentMenus'));
    }

    /**
     * ذخیره ساختار منوی جدید ثبت شده توسط ادمین
     */
    public function store(Request $request)
    {
        $request->validate([
            'title_fa'   => 'required|string',
            'title_en'   => 'required|string',
            'action'     => 'required|string', // نقش مجاز (admin, company, association)
            'route_name' => 'nullable|string',
            'parent_id'  => 'nullable|integer',
            'icon'       => 'nullable|string',
        ]);

        try {
            DB::table('permissions_and_menus')->insert([
                'parent_id'  => $request->input('parent_id'),
                'title_fa'   => $request->input('title_fa'),
                'title_en'   => $request->input('title_en'),
                'route_name' => $request->input('route_name'),
                'icon'       => $request->input('icon') ?? 'link',
                'action'     => $request->input('action'), // ریختن نقش در فیلد اکشن ENUM شما
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'منوی جدید با موفقیت به ساختار پویا اضافه شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در ذخیره‌سازی منو: ' . $e->getMessage());
        }
    }
}

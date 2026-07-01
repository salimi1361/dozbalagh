<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    // نمایش تمام رانندگان سامانه
    public function index()
    {
        // خواندن رانندگان به همراه نام شرکتی که به آن متصل هستند + تعداد دوزبلاغ فعال
        $drivers = DB::table('drivers')
            ->leftJoin('companies', 'drivers.current_company_id', '=', 'companies.id')
            ->select('drivers.*', 'companies.name_fa as company_name')
            ->addSelect([
                'active_dozbalaghs' => DB::table('permit_requests') 
                                        ->whereColumn('permit_requests.driver_id', 'drivers.id') 
                                        ->where('permit_requests.status', 'صادر شده') 
                                        ->selectRaw('count(*)')
            ])
            ->orderBy('drivers.id', 'desc')
            ->get(); 

        // لیست شرکت‌های فعال برای نمایش در منوی کشویی انتقال راننده
        $companies = DB::table('companies')->where('status', 'approved')->select('id', 'name_fa')->get();

        return view('admin.drivers.index', compact('drivers', 'companies'));
    }

    // تغییر شرکتِ راننده توسط ادمین
    public function updateCompany(Request $request, $id)
    {
        $companyId = $request->company_id;
        
        // اگر ادمین آیدی شرکت را روی "آزاد" بگذارد، راننده از شرکت فعلی جدا می‌شود
        DB::table('drivers')->where('id', $id)->update([
            'current_company_id' => empty($companyId) ? null : $companyId,
            'updated_at' => now()
        ]);

        return back()->with('success', 'وضعیت و شرکت راننده با موفقیت بروزرسانی شد.');
    }

    // حذف کامل راننده از سیستم
    public function destroy($id)
    {
        // بررسی پروانه‌های فعال دوزبلاغ قبل از حذف
        $activeCount = DB::table('permit_requests')
                         ->where('driver_id', $id)
                         ->where('status', 'صادر شده')
                         ->count();

        if ($activeCount > 0) {
            return back()->with('error', "خطا: این راننده دارای پروانه فعال است. برای حذف کامل، ابتدا باید پروانه‌های او لغو شود یا اینکه او را از شرکت فعلی «آزاد» کنید.");
        }

        DB::table('drivers')->where('id', $id)->delete();
        return back()->with('success', 'راننده برای همیشه از دیتابیس سامانه حذف شد.');
    }
}
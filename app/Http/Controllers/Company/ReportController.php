<?php

namespace App\Http\Controllers\Company; // <--- اینجا اصلاح شد

use App\Http\Controllers\Controller; // <--- اینجا اضافه شد
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // محاسبه وضعیت‌های ۶ گانه
        $counts = [
            'issued'   => DB::table('permit_requests')->where('status', 'صادر شده')->count(),
            'used'     => DB::table('permit_requests')->where('status', 'استفاده شده')->count(),
            'returned' => DB::table('permit_requests')->where('status', 'تحویل داده شده/لاشه')->count(),
            'expired'  => DB::table('permit_requests')->where('status', 'استفاده نشده')->count(),
            'renewed'  => DB::table('permit_requests')->where('status', 'تمدیدی')->count(),
            'lost'     => DB::table('permit_requests')->where('status', 'مفقودی')->count(),
        ];

        // دریافت لیست کامل جهت نمایش در جدول گزارش
        $permits = DB::table('permit_requests')->orderBy('id', 'desc')->paginate(20);

        return view('company.report.index', compact('counts', 'permits'));
    }
}
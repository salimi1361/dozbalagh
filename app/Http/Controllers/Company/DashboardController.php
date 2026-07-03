<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        if (!$company) {
            return redirect()->route('admin.companies.index');
        }

        $companyId = $company->id;

        // ۱. واکشی موجودی آزاد و بلوکه شده کیف پول شرکت
        $wallet = DB::table('wallets')->where('company_id', $companyId)->first();
        $walletBalance = $wallet ? $wallet->balance : 0;
        $blockedBalance = $wallet ? ($wallet->blocked_balance ?? 0) : 0;

        // ۲. تعداد رانندگان و ناوگان
        $driversCount = DB::table('drivers')->where('current_company_id', $companyId)->count();
        $fleetsCount = DB::table('fleets')->where('company_id', $companyId)->count();

        // ۳. آمار جامع وضعیت پروانه‌های دوزبلاغ شرکت (اصلاح شده)
        $permitStats = DB::table('permit_requests')
            ->where('company_id', $companyId)
            ->selectRaw("
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status IN ('approved', 'issued', 'صادر شده') THEN 1 END) as issued_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_count,
                COUNT(CASE WHEN status IN ('collected', 'archived') THEN 1 END) as collected_count,
                COUNT(CASE WHEN status = 'renewed' THEN 1 END) as renewed_count,
                COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost_count,
                COUNT(*) as total_count
            ")
            ->first();

        // محاسبه نرخ موفقیت درخواست‌ها (پروانه‌های صادر شده به کل)
        $successRate = $permitStats->total_count > 0 
            ? round(($permitStats->issued_count / $permitStats->total_count) * 100) 
            : 0;

        // ۴. آخرین درخواست‌های ثبت شده شرکت با نام راننده و پلاک ناوگان
        $recentRequests = DB::table('permit_requests')
            ->where('permit_requests.company_id', $companyId)
            ->leftJoin('drivers', 'permit_requests.driver_id', '=', 'drivers.id')
            ->leftJoin('fleets', 'permit_requests.fleet_id', '=', 'fleets.id')
            ->select([
                'permit_requests.id',
                'permit_requests.d_code',
                'permit_requests.status',
                'permit_requests.total_amount',
                'permit_requests.created_at',
                'drivers.first_name_fa',
                'drivers.last_name_fa',
                'fleets.transit_plate'
            ])
            ->orderBy('permit_requests.id', 'desc')
            ->limit(6)
            ->get();

        foreach ($recentRequests as $req) {
            $req->jalali_date = $req->created_at 
                ? \Hekmatinasser\Verta\Verta::instance($req->created_at)->format('Y/m/d H:i') 
                : '---';
        }

        return view('dashboard', compact(
            'company', 
            'user', 
            'walletBalance', 
            'blockedBalance',
            'driversCount', 
            'fleetsCount', 
            'permitStats', 
            'successRate',
            'recentRequests'
        ));
    }
}
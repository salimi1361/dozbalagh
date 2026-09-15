<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AssociationCompanyMessage;
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
        $driversCount = DB::table('drivers')->where('current_company_id', $companyId)->where('is_active', true)->count();
        $fleetsCount = DB::table('fleets')->where('company_id', $companyId)->where('is_active', true)->count();

        // ۳. آمار جامع وضعیت پروانه‌های دوزوله شرکت.
        // صفحه «پرونده‌های دوزوله» از وضعیت هر آیتم (نه وضعیت والد درخواست)
        // استفاده می‌کند؛ بنابراین کارت‌های داشبورد هم باید دقیقاً از همان
        // منبع و همان مجموعه وضعیت‌ها شمارش شوند تا لینک هر کارت نتیجهٔ
        // متفاوتی نشان ندهد.
        $permitStats = DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->where('permit.company_id', $companyId)
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status IN ('pending', 'under_review', 'approved', 'cits_processing', 'cits_company_error', 'cits_system_error', 'cits_other_error', 'ready_to_issue') THEN permit.id END) as pending_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status = 'approved' THEN permit.id END) as approved_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status = 'issued' THEN permit.id END) as issued_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status = 'rejected' THEN permit.id END) as rejected_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status IN ('company_returned', 'collected_for_renewal') THEN permit.id END) as returned_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status IN ('archived', 'returned_unused', 'extended', 'cancelled') THEN permit.id END) as collected_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN permit.request_type = 'renewal' THEN permit.id END) as renewal_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN (permit.request_type IS NULL OR permit.request_type = '' OR permit.request_type = 'new') THEN permit.id END) as new_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status IN ('lost_reported', 'lost') THEN permit.id END) as lost_count")
            ->selectRaw("COUNT(DISTINCT permit.id) as total_count")
            ->first();

        // محاسبه نرخ موفقیت درخواست‌ها (پروانه‌های صادر شده به کل)
        $successfulCount = ($permitStats->issued_count ?? 0) + ($permitStats->collected_count ?? 0) + ($permitStats->lost_count ?? 0);
        $successRate = $permitStats->total_count > 0 
            ? round(($successfulCount / $permitStats->total_count) * 100) 
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
                'permit_requests.request_type',
                'permit_requests.reject_reason',
                'permit_requests.company_note',
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

        $pendingAssociationMessages = AssociationCompanyMessage::query()
            ->visibleForCompany($companyId)
            ->where('is_mandatory', true)
            ->whereDoesntHave('receipts', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->whereNotNull('acknowledged_at');
            })
            ->latest()
            ->get();

        $expiringPermits = DB::table('permit_request_items as item')
            ->join('permit_requests as request', 'request.id', '=', 'item.permit_request_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->where('request.company_id', $companyId)
            ->whereIn('item.item_status', ['issued', 'transferred_in'])
            ->whereNotNull('item.permit_valid_until')
            ->whereRaw('COALESCE(item.validity_days, country.validity_days, 0) > 0')
            ->whereDate('item.permit_valid_until', '<=', today()->addDays(10))
            ->whereExists(function ($mission) {
                $mission->selectRaw('1')->from('permit_courier_missions as mission')
                    ->whereColumn('mission.permit_request_item_id', 'item.id')
                    ->where('mission.direction', 'association_to_company')
                    ->where('mission.status', 'completed');
            })
            ->select([
                'item.id', 'item.d_serial_number', 'item.permit_valid_until',
                'request.d_code', 'country.name as country_name',
                DB::raw('DATEDIFF(item.permit_valid_until, CURDATE()) as expiry_days_remaining'),
            ])
            ->orderBy('item.permit_valid_until')
            ->limit(20)
            ->get();

        return view('company.dashboard', compact(
            'company', 
            'user', 
            'walletBalance', 
            'blockedBalance',
            'driversCount', 
            'fleetsCount', 
            'permitStats', 
            'successRate',
            'recentRequests',
            'pendingAssociationMessages',
            'expiringPermits'
        ));
    }
}

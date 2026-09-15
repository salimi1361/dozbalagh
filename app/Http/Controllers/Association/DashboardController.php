<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\PermitRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $companiesCount = Company::count();
        $driversCount = Driver::active()->count();
        $fleetsCount = Fleet::active()->count();
        $pendingRequestsCount = PermitRequest::where('status', 'pending')->count();

        $weekStart = Carbon::now()->subDays(6)->startOfDay();
        $dailyRequests = DB::table('permit_requests')
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->where('created_at', '>=', $weekStart)
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $weekdayNames = [
            0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه',
            4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه',
        ];

        $requestChartLabels = [];
        $requestChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $requestChartLabels[] = $weekdayNames[$date->dayOfWeek] ?? $date->format('Y/m/d');
            $requestChartData[] = (int) ($dailyRequests[$date->toDateString()] ?? 0);
        }

        $destinationRows = DB::table('permit_request_items as pri')
            ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
            ->selectRaw("COALESCE(c.name, 'نامشخص') as country_name, COUNT(*) as total")
            ->groupByRaw("COALESCE(c.name, 'نامشخص')")
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $destinationChartLabels = $destinationRows->pluck('country_name')->values();
        $destinationChartData = $destinationRows->map(fn ($row) => (int) $row->total)->values();
        $destinationTotal = $destinationChartData->sum();

        $availableStatuses = ['raw', 'in_stock'];
        $lowStockCountries = DB::table('dozbalagh_items as di')
            ->join('dozbalagh_batches as db', 'di.batch_id', '=', 'db.id')
            ->leftJoin('countries as c', 'db.country_id', '=', 'c.id')
            ->where(function ($query) use ($availableStatuses) {
                $query->whereNull('di.lifecycle_status')
                    ->orWhereIn('di.lifecycle_status', $availableStatuses);
            })
            ->selectRaw("COALESCE(c.name, db.country_name, 'نامشخص') as country_name, COALESCE(db.permit_type, 'unclassified') as permit_type, COUNT(di.id) as available_count")
            ->groupByRaw("COALESCE(c.name, db.country_name, 'نامشخص'), COALESCE(db.permit_type, 'unclassified')")
            ->having('available_count', '<=', 50)
            ->orderBy('available_count')
            ->limit(4)
            ->get();

        return view('admin.dashboard', compact(
            'companiesCount', 'driversCount', 'fleetsCount', 'pendingRequestsCount',
            'requestChartLabels', 'requestChartData', 'destinationChartLabels',
            'destinationChartData', 'destinationTotal', 'lowStockCountries',
        ));
    }
}

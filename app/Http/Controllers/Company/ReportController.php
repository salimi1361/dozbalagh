<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $baseQuery = DB::table('permit_requests')->where('company_id', $companyId);

        $counts = [
            'issued'    => (clone $baseQuery)->where('status', 'issued')->count(),
            'pending'   => (clone $baseQuery)->whereIn('status', ['pending', 'approved'])->count(),
            'collected' => (clone $baseQuery)->whereIn('status', ['collected', 'archived'])->count(),
            'returned'  => (clone $baseQuery)->where('status', 'returned')->count(),
            'renewed'   => (clone $baseQuery)->where('request_type', 'renewal')->count(),
            'lost'      => (clone $baseQuery)->where('status', 'lost')->count(),
        ];

        $permits = DB::table('permit_requests')
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('company.report.index', compact('counts', 'permits'));
    }
}

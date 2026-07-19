<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyVerificationHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CompanyManualStatusController extends Controller
{
    public function show(Request $request): View
    {
        $company = $request->user()->company;

        return view('Shahbaz.company.manual-status.show', [
            'company' => $company,
            'histories' => CompanyVerificationHistory::with('changedBy')
                ->where('company_id', $company->id)
                ->where('result', 'manual_status_changed')
                ->latest('id')
                ->get(),
        ]);
    }
}

<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\LicenseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CompanyLicensesController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        return view('Shahbaz.company.licenses.index', [
            'company' => $company,
            'requests' => LicenseRequest::where('company_id', $company->id)
                ->whereNotNull('previous_license_number')->latest()->get(),
            'editable' => in_array($company->shahbaz_verification_status, ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'], true),
        ]);
    }
}

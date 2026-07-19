<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CompanyFleetDossierController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        $fleets = Fleet::where('company_id', $company->id)->latest()->get();

        return view('Shahbaz.company.fleet.index', [
            'company' => $company,
            'fleets' => $fleets,
            'editable' => in_array($company->shahbaz_verification_status, ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'], true),
        ]);
    }
}

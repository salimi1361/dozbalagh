<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Services\ShahbazSectionService;
use Illuminate\Http\Request;

class CompanyDossierController extends Controller
{
    public function show(Request $request, ShahbazSectionService $sections)
    {
        $company = $request->user()->company;
        return view('Shahbaz.company.dossier', [
            'company' => $company, 'sections' => $sections->accessibleRows($company),
            'reminders' => $sections->reminders(),
        ]);
    }
}

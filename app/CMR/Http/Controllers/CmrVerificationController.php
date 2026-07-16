<?php

namespace App\CMR\Http\Controllers;

use App\CMR\Models\CmrDocument;
use App\Http\Controllers\Controller;

class CmrVerificationController extends Controller
{
    public function show(string $code)
    {
        $cmr = CmrDocument::with(['company', 'goods', 'signatures'])
            ->where('verification_code', $code)->whereNotNull('issued_at')->firstOrFail();
        return view('CMR.verify', compact('cmr'));
    }
}

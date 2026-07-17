<?php

namespace App\CMR\Http\Controllers;

use App\CMR\Models\CmrDocument;
use App\Http\Controllers\Controller;

class CmrVerificationController extends Controller
{
    public function show(string $code)
    {
        $cmr = CmrDocument::with(['company', 'goods', 'signatures', 'versions'])
            ->where('verification_code', $code)->whereNotNull('issued_at')->firstOrFail();
        $version = $cmr->versions->firstWhere('version', $cmr->version);
        $integrityValid = $version !== null && hash_equals((string) $version->integrity_hash, (string) $cmr->integrity_hash);

        return view('CMR.verify', compact('cmr', 'integrityValid'));
    }
}

<?php

namespace App\Shahbaz\Http\Middleware;

use App\Shahbaz\Services\CompanyEligibilityService;
use Closure;
use Illuminate\Http\Request;

class EnsureCompanyEligible
{
    public function handle(Request $request, Closure $next, CompanyEligibilityService $eligibility)
    {
        $company = $request->user()?->company;
        abort_unless($company, 403);

        if (!$eligibility->canOperate($company)) {
            return redirect()->route('company.shahbaz.profile.edit')
                ->with('warning', implode(' ', $eligibility->blockingReasons($company)));
        }

        return $next($request);
    }
}

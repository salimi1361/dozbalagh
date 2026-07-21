<?php

namespace App\Shahbaz\Http\Middleware;

use App\Shahbaz\Services\CompanyEligibilityService;
use Closure;
use Illuminate\Http\Request;

class EnsureCompanyEligible
{
    public function __construct(private readonly CompanyEligibilityService $eligibility)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $company = $request->user()?->company;
        abort_unless($company, 403);

        if (!$this->eligibility->canOperate($company)) {
            return redirect()->route('company.shahbaz.profile.edit')
                ->with('warning', implode(' ', $this->eligibility->blockingReasons($company)));
        }

        return $next($request);
    }
}

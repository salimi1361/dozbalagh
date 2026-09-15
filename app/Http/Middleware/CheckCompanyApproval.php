<?php

namespace App\Http\Middleware;

use App\Shahbaz\Services\CompanyEligibilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyApproval
{
    public function __construct(private readonly CompanyEligibilityService $eligibility)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if (! $company || $this->eligibility->gateMode() === CompanyEligibilityService::GATE_DISABLED) {
            return $next($request);
        }

        // The wallet remains available during onboarding so a non-member can
        // charge it before a paid renewal or another billable operation.
        if ($request->routeIs('company.wallet.*')) {
            return $next($request);
        }

        if (! $this->eligibility->canOperate($company)) {
            return redirect()
                ->route($this->eligibility->onboardingRoute($company))
                ->with('warning', implode(' ', $this->eligibility->blockingReasons($company)));
        }

        return $next($request);
    }
}

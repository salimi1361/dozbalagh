<?php

namespace App\Http\Middleware;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrPrintTemplate;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmrCompanyScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('company')) {
            return $next($request);
        }

        $company = $request->user()->company;
        abort_unless($company, 403, 'شرکت مرتبط با این حساب کاربری یافت نشد.');

        $request->merge(['company_id' => $company->id]);

        $this->assertOwnedRouteParameter($request->route('cmr'), CmrDocument::class, $company->id);
        $this->assertCompanyRouteParameter($request->route('company'), $company->id);
        $this->assertOwnedRouteParameter($request->route('template'), CmrPrintTemplate::class, $company->id);

        if ($request->routeIs('admin.cmr.reports.*')) {
            abort(403);
        }

        if ($request->routeIs('admin.cmr.settings.*') && ! $request->isMethod('GET')) {
            abort(403, 'تعرفه سراسری CMR فقط توسط مدیر کل قابل تغییر است.');
        }

        return $next($request);
    }

    private function assertOwnedRouteParameter(mixed $parameter, string $model, int $companyId): void
    {
        if ($parameter === null) {
            return;
        }

        $record = $parameter instanceof $model ? $parameter : $model::findOrFail($parameter);
        abort_unless((int) $record->company_id === $companyId, 404);
    }

    private function assertCompanyRouteParameter(mixed $parameter, int $companyId): void
    {
        if ($parameter === null) {
            return;
        }

        $company = $parameter instanceof Company ? $parameter : Company::findOrFail($parameter);
        abort_unless((int) $company->id === $companyId, 404);
    }
}

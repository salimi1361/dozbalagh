<?php

namespace App\Http\Middleware;

use App\Models\Country;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequestablePermitTypes
{
    public function handle(Request $request, Closure $next): Response
    {
        $permitTypeLabels = config('permit_types.labels', []);

        foreach ((array) $request->input('destinations', []) as $destination) {
            $country = Country::find($destination['country_id'] ?? null);
            $permitType = trim((string) ($destination['permit_type'] ?? ''));

            if ($country && in_array($permitType, $country->requestablePermitTypes(), true)) {
                continue;
            }

            $countryName = $country?->name ?? 'انتخاب‌شده';
            $permitLabel = $permitTypeLabels[$permitType] ?? ($permitType ?: 'نامشخص');

            return back()
                ->withErrors([
                    'destinations' => "نوع مجوز «{$permitLabel}» برای کشور «{$countryName}» غیرفعال است و در درخواست جدید قابل انتخاب نیست.",
                ])
                ->withInput();
        }

        return $next($request);
    }
}

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

            if ($country?->is_active && in_array($permitType, $country->requestablePermitTypes(), true)) {
                continue;
            }

            $countryName = $country?->name ?? 'انتخاب‌شده';
            $permitLabel = $permitTypeLabels[$permitType] ?? ($permitType ?: 'نامشخص');
            $message = !$country
                ? "نوع مجوز «{$permitLabel}» برای کشور «{$countryName}» غیرفعال است و در درخواست جدید قابل انتخاب نیست."
                : (! $country->is_active
                    ? "کشور «{$countryName}» در حال حاضر غیرفعال است و در درخواست جدید قابل انتخاب نیست."
                    : "نوع مجوز «{$permitLabel}» برای کشور «{$countryName}» غیرفعال است و در درخواست جدید قابل انتخاب نیست.");

            return back()
                ->withErrors([
                    'destinations' => $message,
                ])
                ->withInput();
        }

        return $next($request);
    }
}

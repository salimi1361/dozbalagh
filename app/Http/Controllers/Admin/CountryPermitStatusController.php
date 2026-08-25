<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CountryPermitStatusController extends Controller
{
    private const PERMIT_TYPES = [
        'bilateral' => 'دوجانبه',
        'transit' => 'ترانزیت',
        'bilateral_transit' => 'دوجانبه ترانزیت',
        'third_country_transit' => 'ترانزیت کشور ثالث',
        'third_country' => 'کشور ثالث',
    ];

    public function index(): View
    {
        return view('admin.countries.permit-statuses', [
            'countries' => Country::query()->orderBy('name')->get(),
            'permitTypes' => self::PERMIT_TYPES,
        ]);
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $validated = $request->validate([
            'permit_types' => ['nullable', 'array'],
            'permit_types.*' => ['string', 'distinct', Rule::in(array_keys(self::PERMIT_TYPES))],
        ], [
            'permit_types.*.in' => 'یکی از انواع مجوز انتخاب‌شده معتبر نیست.',
        ]);

        $activeTypes = array_values(array_intersect(
            array_keys(self::PERMIT_TYPES),
            $validated['permit_types'] ?? [],
        ));

        $country->forceFill(['allowed_permit_types' => $activeTypes])->save();

        return back()->with(
            'success',
            "وضعیت مجوزهای کشور «{$country->name}» ذخیره شد.",
        );
    }
}

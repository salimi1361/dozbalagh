<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Services\CompanyEligibilityService;
use App\Shahbaz\Services\ShahbazSectionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSettingsController extends Controller
{
    public function edit(ShahbazSectionService $sections, CompanyEligibilityService $eligibility)
    {
        return view('Shahbaz.admin.settings', [
            'rows' => $sections->rows(),
            'reminders' => $sections->reminders(),
            'dozbalaghGateMode' => $eligibility->gateMode(),
        ]);
    }

    public function update(Request $request, ShahbazSectionService $sections, CompanyEligibilityService $eligibility)
    {
        $validated = $request->validate([
            'sections' => ['nullable', 'array'], 'reminder_days' => ['required', 'array', 'min:1'],
            'reminder_days.*' => ['integer', 'min:0', 'max:365'], 'sms_enabled' => ['nullable', 'boolean'],
            'panel_enabled' => ['nullable', 'boolean'],
            'dozbalagh_gate_mode' => ['required', Rule::in(CompanyEligibilityService::GATE_MODES)],
        ]);
        $sections->save($validated);
        $eligibility->setGateMode($validated['dozbalagh_gate_mode']);
        return back()->with('success', 'تنظیمات مراحل پرونده شحباز ذخیره شد.');
    }
}

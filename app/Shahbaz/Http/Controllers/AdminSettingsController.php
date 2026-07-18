<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Services\ShahbazSectionService;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function edit(ShahbazSectionService $sections)
    {
        return view('Shahbaz.admin.settings', ['rows' => $sections->rows(), 'reminders' => $sections->reminders()]);
    }

    public function update(Request $request, ShahbazSectionService $sections)
    {
        $validated = $request->validate([
            'sections' => ['nullable', 'array'], 'reminder_days' => ['required', 'array', 'min:1'],
            'reminder_days.*' => ['integer', 'min:0', 'max:365'], 'sms_enabled' => ['nullable', 'boolean'],
            'panel_enabled' => ['nullable', 'boolean'],
        ]);
        $sections->save($validated);
        return back()->with('success', 'تنظیمات مراحل پرونده شحباز ذخیره شد.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileAppVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileAppVersionController extends Controller
{
    public function index(): View
    {
        return view('admin.mobile_versions.index', [
            'versions' => MobileAppVersion::query()->get()->keyBy('platform'),
        ]);
    }

    public function update(Request $request, string $platform): RedirectResponse
    {
        abort_unless(in_array($platform, ['android', 'ios'], true), 404);
        $validated = $request->validate([
            'version_name' => ['required', 'string', 'max:50'],
            'latest_build' => ['required', 'integer', 'min:1'],
            'minimum_build' => ['required', 'integer', 'min:1', 'lte:latest_build'],
            'download_url' => ['required', 'url', 'max:255'],
            'file_checksum' => ['nullable', 'string', 'max:128'],
            'message' => ['nullable', 'string', 'max:2000'],
            'release_notes' => ['nullable', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ]);

        MobileAppVersion::updateOrCreate(['platform' => $platform], $validated + [
            'force_update' => $request->boolean('force_update'),
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'تنظیمات نسخه اپلیکیشن ذخیره شد.');
    }
}

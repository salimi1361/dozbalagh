<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendMobileAppUpdatePush;
use App\Models\MobileAppInstallation;
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
            'pushRecipients' => MobileAppInstallation::query()
                ->selectRaw('platform, COUNT(*) as total')
                ->whereNull('revoked_at')
                ->where('notifications_enabled', true)
                ->whereNotNull('fcm_token')
                ->groupBy('platform')
                ->pluck('total', 'platform'),
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

        $version = MobileAppVersion::updateOrCreate(['platform' => $platform], $validated + [
            'force_update' => $request->boolean('force_update'),
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $recipientCount = 0;
        if ($request->boolean('send_push_notification') && $version->is_active) {
            $recipientCount = MobileAppInstallation::query()
                ->where('platform', $platform)
                ->whereNull('revoked_at')
                ->where('notifications_enabled', true)
                ->whereNotNull('fcm_token')
                ->count();
            SendMobileAppUpdatePush::dispatch($version->id);
        }

        $message = 'تنظیمات نسخه اپلیکیشن ذخیره شد.';
        if ($request->boolean('send_push_notification')) {
            $message .= $recipientCount > 0
                ? " اعلان بروزرسانی برای {$recipientCount} دستگاه در صف ارسال قرار گرفت."
                : ' دستگاه فعالی با توکن اعلان برای این پلتفرم پیدا نشد.';
        }

        return back()->with('success', $message);
    }
}

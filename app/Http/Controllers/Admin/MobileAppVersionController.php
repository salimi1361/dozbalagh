<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendMobileAppUpdatePush;
use App\Models\MobileAppInstallation;
use App\Models\MobileAppVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
        $currentVersion = MobileAppVersion::query()->where('platform', $platform)->first();
        $validated = $request->validate([
            'version_name' => ['required', 'string', 'max:50'],
            'latest_build' => ['required', 'integer', 'min:1'],
            'minimum_build' => ['required', 'integer', 'min:1', 'lte:latest_build'],
            'download_url' => ['nullable', 'url', 'max:2048'],
            'release_file' => ['nullable', 'file', 'max:256000'],
            'file_checksum' => ['nullable', 'string', 'max:128'],
            'message' => ['nullable', 'string', 'max:2000'],
            'release_notes' => ['nullable', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ]);

        $releaseFile = $request->file('release_file');
        if ($releaseFile) {
            $expectedExtension = $platform === 'android' ? 'apk' : 'ipa';
            if (strtolower($releaseFile->getClientOriginalExtension()) !== $expectedExtension) {
                throw ValidationException::withMessages([
                    'release_file' => "فایل نسخه {$platform} باید با پسوند .{$expectedExtension} باشد.",
                ]);
            }

            $safeVersion = preg_replace('/[^A-Za-z0-9._-]+/', '-', $validated['version_name']);
            $fileName = sprintf(
                '%s-%s-build-%d-%s.%s',
                $platform,
                trim((string) $safeVersion, '-'),
                $validated['latest_build'],
                now()->format('Ymd-His'),
                $expectedExtension,
            );
            $path = $releaseFile->storeAs('app-releases', $fileName, 'public');
            $validated['download_url'] = url(Storage::disk('public')->url($path));
            $validated['file_checksum'] = hash_file('sha256', Storage::disk('public')->path($path));
        } elseif (blank($validated['download_url'] ?? null)) {
            if (! $currentVersion?->download_url) {
                throw ValidationException::withMessages([
                    'download_url' => 'یک فایل نسخه آپلود کنید یا لینک دریافت را وارد کنید.',
                ]);
            }
            $validated['download_url'] = $currentVersion->download_url;
        }

        unset($validated['release_file']);

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

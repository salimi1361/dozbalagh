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
use Morilog\Jalali\Jalalian;

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
        if ($request->filled('published_at_jalali')) {
            $request->merge([
                'published_at_jalali' => strtr((string) $request->input('published_at_jalali'), [
                    '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                    '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                ]),
            ]);
        }
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
            'published_at_jalali' => [
                'nullable',
                'string',
                'regex:/^1[34-9]\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01]) ([01]\d|2[0-3]):[0-5]\d$/',
            ],
        ]);

        $publishedAtJalali = $validated['published_at_jalali'] ?? null;
        unset($validated['published_at_jalali']);
        if ($publishedAtJalali) {
            try {
                $validated['published_at'] = Jalalian::fromFormat('Y/m/d H:i', $publishedAtJalali)->toCarbon();
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'published_at_jalali' => 'زمان انتشار شمسی معتبر نیست.',
                ]);
            }
        } else {
            $validated['published_at'] = null;
        }

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

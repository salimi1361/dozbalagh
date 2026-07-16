<?php

namespace App\Jobs;

use App\Models\MobileAppInstallation;
use App\Models\MobileAppVersion;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMobileAppUpdatePush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $versionId)
    {
    }

    public function handle(FirebaseCloudMessaging $firebase): void
    {
        $version = MobileAppVersion::query()->find($this->versionId);

        if (! $version || ! $version->is_active) {
            return;
        }

        $payload = [
            'type' => 'app_update',
            'platform' => $version->platform,
            'version_name' => $version->version_name,
            'latest_build' => $version->latest_build,
            'minimum_build' => $version->minimum_build,
            'force_update' => $version->force_update,
            'download_url' => $version->download_url,
            'title' => $version->force_update
                ? 'بروزرسانی الزامی اپلیکیشن راننده'
                : 'نسخه جدید اپلیکیشن راننده',
            'body' => $version->message ?: "نسخه {$version->version_name} اپلیکیشن آماده دریافت است.",
            'priority' => $version->force_update ? 'urgent' : 'important',
        ];

        MobileAppInstallation::query()
            ->where('platform', $version->platform)
            ->whereNull('revoked_at')
            ->where('notifications_enabled', true)
            ->whereNotNull('fcm_token')
            ->eachById(function (MobileAppInstallation $installation) use ($firebase, $payload): void {
                try {
                    $response = $firebase->send($installation->fcm_token, $payload);

                    if ($firebase->tokenIsInvalid($response)) {
                        $installation->forceFill([
                            'fcm_token' => null,
                            'fcm_token_updated_at' => null,
                        ])->save();
                    } elseif ($response->failed()) {
                        Log::warning('Firebase app update push failed.', [
                            'installation_id' => $installation->id,
                            'status' => $response->status(),
                        ]);
                    }
                } catch (Throwable $exception) {
                    Log::error('Firebase app update push error.', [
                        'installation_id' => $installation->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}

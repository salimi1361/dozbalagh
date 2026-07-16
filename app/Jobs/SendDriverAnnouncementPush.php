<?php

namespace App\Jobs;

use App\Models\DriverAnnouncement;
use App\Models\MobileAppInstallation;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDriverAnnouncementPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $announcementId)
    {
    }

    public function handle(FirebaseCloudMessaging $firebase): void
    {
        $announcement = DriverAnnouncement::query()
            ->with('receipts:id,announcement_id,driver_id')
            ->find($this->announcementId);

        if (! $announcement || ! $announcement->is_active || ($announcement->ends_at && $announcement->ends_at->isPast())) {
            return;
        }

        $payload = [
            'type' => 'driver_announcement',
            'announcement_id' => $announcement->id,
            'title' => $announcement->title,
            'body' => $announcement->message,
            'priority' => $announcement->priority,
            'display_mode' => $announcement->display_mode,
            'requires_acknowledgement' => $announcement->requires_acknowledgement,
        ];

        MobileAppInstallation::query()
            ->whereIn('driver_id', $announcement->receipts->pluck('driver_id'))
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
                        Log::warning('Firebase driver announcement push failed.', [
                            'installation_id' => $installation->id,
                            'status' => $response->status(),
                        ]);
                    }
                } catch (Throwable $exception) {
                    Log::error('Firebase driver announcement push error.', [
                        'installation_id' => $installation->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}

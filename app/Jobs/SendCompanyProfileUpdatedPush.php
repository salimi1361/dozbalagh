<?php

namespace App\Jobs;

use App\Models\MobileAppInstallation;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCompanyProfileUpdatedPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $companyId)
    {
    }

    public function handle(FirebaseCloudMessaging $firebase): void
    {
        $payload = [
            'type' => 'company_profile_updated',
            'company_id' => $this->companyId,
            'title' => 'اطلاعات شرکت به‌روزرسانی شد',
            'body' => 'اطلاعات شرکت متصل به حساب شما تازه شد.',
            'priority' => 'high',
            'silent' => true,
        ];

        MobileAppInstallation::query()
            ->whereHas('driver', fn ($query) => $query
                ->where('current_company_id', $this->companyId))
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
                        Log::warning('Firebase company profile refresh push failed.', [
                            'installation_id' => $installation->id,
                            'company_id' => $this->companyId,
                            'status' => $response->status(),
                        ]);
                    }
                } catch (Throwable $exception) {
                    Log::error('Firebase company profile refresh push error.', [
                        'installation_id' => $installation->id,
                        'company_id' => $this->companyId,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}

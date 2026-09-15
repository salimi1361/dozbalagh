<?php

namespace App\Jobs;

use App\CMR\Models\CmrDocument;
use App\Models\MobileAppInstallation;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCmrDriverPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $documentId)
    {
    }

    public function handle(FirebaseCloudMessaging $firebase): void
    {
        $document = CmrDocument::query()->with('company')->find($this->documentId);

        if (! $document || ! $document->driver_id || $document->status === 'draft') {
            return;
        }

        $number = $document->company_serial ?: $document->number;
        $company = $document->company?->name_fa ?: $document->company?->name_en ?: 'شرکت حمل‌ونقل';
        $payload = [
            'type' => 'cmr_issued',
            'cmr_id' => $document->id,
            'driver_id' => $document->driver_id,
            'company_id' => $document->company_id,
            'title' => 'CMR جدید برای شما صادر شد',
            'body' => "سند {$number} توسط {$company} صادر شد؛ برای بررسی و تحویل‌گیری وارد برنامه شوید.",
            'status' => $document->status,
        ];

        MobileAppInstallation::query()
            ->where('driver_id', $document->driver_id)
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
                        Log::warning('Firebase CMR push failed.', [
                            'installation_id' => $installation->id,
                            'cmr_id' => $this->documentId,
                            'status' => $response->status(),
                        ]);
                    }
                } catch (Throwable $exception) {
                    Log::error('Firebase CMR push error.', [
                        'installation_id' => $installation->id,
                        'cmr_id' => $this->documentId,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}

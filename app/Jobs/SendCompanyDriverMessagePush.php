<?php

namespace App\Jobs;

use App\Models\CompanyDriverMessage;
use App\Models\MobileAppInstallation;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCompanyDriverMessagePush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $messageId)
    {
    }

    public function handle(FirebaseCloudMessaging $firebase): void
    {
        $message = CompanyDriverMessage::query()->find($this->messageId);

        if (! $message || $message->sender !== 'company') {
            return;
        }

        $payload = [
            'type' => 'company_message',
            'message_id' => $message->id,
            'company_id' => $message->company_id,
            'driver_id' => $message->driver_id,
            'conversation_id' => 'company:' . $message->company_id,
            'title' => $message->title,
            'body' => $message->message,
            'priority' => $message->priority ?: 'normal',
        ];

        MobileAppInstallation::query()
            ->where('driver_id', $message->driver_id)
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
                        Log::warning('Firebase company message push failed.', [
                            'installation_id' => $installation->id,
                            'status' => $response->status(),
                            'response' => $response->json(),
                        ]);
                    }
                } catch (Throwable $exception) {
                    Log::error('Firebase company message push error.', [
                        'installation_id' => $installation->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DriverPermitIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $permitId,
        private readonly string $serialNumber,
        private readonly string $companyName,
        private readonly ?string $validUntil = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'permit_issued',
            'title' => 'صدور دوزوله',
            'message' => "دوزوله شماره {$this->serialNumber} توسط شرکت {$this->companyName} برای شما صادر شد.",
            'permit_id' => $this->permitId,
            'serial_number' => $this->serialNumber,
            'company_name' => $this->companyName,
            'valid_until' => $this->validUntil,
        ];
    }
}

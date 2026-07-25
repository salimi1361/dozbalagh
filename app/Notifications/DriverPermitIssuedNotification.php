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
        private readonly ?string $validUntil = null,
        private readonly bool $isRenewal = false
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->isRenewal ? 'permit_renewed' : 'permit_issued',
            'title' => $this->isRenewal ? 'تمدید دوزوله' : 'صدور دوزوله',
            'message' => $this->isRenewal
                ? "دوزوله شما به شماره {$this->serialNumber} توسط شرکت {$this->companyName} تمدید شد."
                : "دوزوله شماره {$this->serialNumber} توسط شرکت {$this->companyName} برای شما صادر شد.",
            'permit_id' => $this->permitId,
            'serial_number' => $this->serialNumber,
            'company_name' => $this->companyName,
            'valid_until' => $this->validUntil,
        ];
    }
}

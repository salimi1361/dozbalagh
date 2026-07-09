<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyDriverMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $companyId,
        private readonly string $companyName,
        private readonly string $title,
        private readonly string $message,
        private readonly string $category = 'general',
        private readonly string $priority = 'normal',
        private readonly bool $requiresAcknowledgement = false,
        private readonly ?string $expiresAt = null,
        private readonly ?int $messageId = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'company_message',
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
            'priority' => $this->priority,
            'requires_acknowledgement' => $this->requiresAcknowledgement,
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'expires_at' => $this->expiresAt,
            'message_id' => $this->messageId,
        ];
    }
}

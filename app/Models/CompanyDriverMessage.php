<?php

namespace App\Models;

use App\Jobs\SendCompanyDriverMessagePush;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDriverMessage extends Model
{
    protected static function booted(): void
    {
        static::created(function (self $message): void {
            if ($message->sender === 'company') {
                SendCompanyDriverMessagePush::dispatch($message->getKey())->afterCommit();
            }
        });
    }

    protected $fillable = [
        'company_id',
        'driver_id',
        'sender',
        'title',
        'message',
        'category',
        'priority',
        'requires_acknowledgement',
        'notification_id',
        'read_at',
    ];

    protected $casts = [
        'requires_acknowledgement' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

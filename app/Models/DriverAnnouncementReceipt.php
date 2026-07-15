<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAnnouncementReceipt extends Model
{
    protected $fillable = [
        'announcement_id', 'driver_id', 'delivered_at', 'seen_at',
        'acknowledged_at', 'device_uuid', 'last_ip',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(DriverAnnouncement::class, 'announcement_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

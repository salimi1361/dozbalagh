<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileAppInstallation extends Model
{
    protected $hidden = ['fcm_token', 'device_pin_hash'];

    protected $fillable = [
        'driver_id', 'device_uuid', 'platform', 'manufacturer', 'model',
        'device_name', 'os_version', 'sdk_version', 'app_version', 'app_build',
        'app_identifier', 'locale', 'last_ip', 'installed_at', 'last_seen_at',
        'fcm_token', 'fcm_token_updated_at', 'notifications_enabled',
        'device_pin_hash', 'device_pin_updated_at',
        'revoked_at', 'revoked_by_user_id',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'fcm_token_updated_at' => 'datetime',
        'notifications_enabled' => 'boolean',
        'device_pin_updated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileAppInstallation extends Model
{
    protected $fillable = [
        'driver_id', 'device_uuid', 'platform', 'manufacturer', 'model',
        'device_name', 'os_version', 'sdk_version', 'app_version', 'app_build',
        'app_identifier', 'locale', 'last_ip', 'installed_at', 'last_seen_at',
        'revoked_at', 'revoked_by_user_id',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}

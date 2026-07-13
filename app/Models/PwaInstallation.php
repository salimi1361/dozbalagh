<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PwaInstallation extends Model
{
    protected $fillable = [
        'actor_type', 'actor_id', 'device_uuid', 'role', 'platform', 'browser',
        'device_type', 'user_agent', 'last_ip', 'is_installed', 'is_standalone',
        'installed_at', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'is_installed' => 'boolean',
        'is_standalone' => 'boolean',
        'installed_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}

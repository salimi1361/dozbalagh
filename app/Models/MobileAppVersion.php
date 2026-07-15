<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileAppVersion extends Model
{
    protected $fillable = [
        'platform', 'version_name', 'latest_build', 'minimum_build',
        'force_update', 'maintenance_mode', 'download_url', 'file_checksum',
        'message', 'release_notes', 'published_at', 'is_active',
    ];

    protected $casts = [
        'force_update' => 'boolean',
        'maintenance_mode' => 'boolean',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}

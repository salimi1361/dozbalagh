<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverAnnouncement extends Model
{
    protected $fillable = [
        'created_by_user_id', 'company_id', 'source_role', 'title', 'message',
        'priority', 'display_mode', 'audience_type', 'show_once',
        'requires_acknowledgement', 'acknowledgement_text', 'starts_at',
        'ends_at', 'is_active',
    ];

    protected $casts = [
        'show_once' => 'boolean',
        'requires_acknowledgement' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(DriverAnnouncementReceipt::class, 'announcement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDriverMessage extends Model
{
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

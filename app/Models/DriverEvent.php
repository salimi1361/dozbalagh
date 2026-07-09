<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverEvent extends Model
{
    protected $fillable = [
        'driver_id',
        'dozbalagh_item_id',
        'event_type',
        'description',
        'latitude',
        'longitude',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function dozbalaghItem(): BelongsTo
    {
        return $this->belongsTo(DozbalaghItem::class);
    }
}

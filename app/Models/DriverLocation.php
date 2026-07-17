<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = [
        'client_uuid',
        'driver_id',
        'dozbalagh_item_id',
        'cmr_document_id',
        'latitude',
        'longitude',
        'accuracy',
        'altitude',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'altitude' => 'float',
        'speed' => 'float',
        'heading' => 'float',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function dozbalaghItem(): BelongsTo
    {
        return $this->belongsTo(DozbalaghItem::class);
    }

    public function cmrDocument(): BelongsTo
    {
        return $this->belongsTo(\App\CMR\Models\CmrDocument::class);
    }
}

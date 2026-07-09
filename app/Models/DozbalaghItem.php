<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DozbalaghItem extends Model
{
    protected $fillable = [
        'batch_id',
        'association_id', // 👈 حتماً این را اضافه کن
        'serial_number',
        'company_id',
        'driver_id',
        'fleet_id',
        'lifecycle_status',
        'issued_at',
        'returned_at'
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DozbalaghBatch::class, 'batch_id');
    }

    public function permitRequest(): HasOne
    {
        return $this->hasOne(PermitRequest::class, 'serial_number', 'serial_number');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DriverEvent::class);
    }
}

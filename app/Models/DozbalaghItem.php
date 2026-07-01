<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
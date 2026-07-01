<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DozbalaghBatch extends Model
{
    protected $fillable = [
        'country_id',
        'country_name',
        'serial_start',
        'serial_end',
        'total_quantity',
        'expiry_date',
        'status'
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DozbalaghItem::class, 'batch_id');
    }
}
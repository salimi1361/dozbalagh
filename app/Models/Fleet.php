<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fleet extends Model
{
    protected $fillable = [
        'company_id',
        'transit_plate',
        'smart_card_number',
        'truck_type',
        'is_manual'
    ];

    // کامیون متعلق به کدام شرکت حمل‌ونقل بین‌المللی است
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // دوزبلاغ‌هایی که برای این کامیون صادر شده است
    public function dozbalaghs(): HasMany
    {
        return $this->hasMany(DozbalaghItem::class);
    }
}
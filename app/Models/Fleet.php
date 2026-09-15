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
        'fleet_loading_type_id',
        'loading_type_code',
        'loading_type_source',
        'organization_payload',
        'is_manual',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
        'organization_payload' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    // کامیون متعلق به کدام شرکت حمل‌ونقل بین‌المللی است
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function loadingType(): BelongsTo
    {
        return $this->belongsTo(FleetLoadingType::class, 'fleet_loading_type_id');
    }

    // دوزبلاغ‌هایی که برای این کامیون صادر شده است
    public function dozbalaghs(): HasMany
    {
        return $this->hasMany(DozbalaghItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetLoadingType extends Model
{
    protected $fillable = [
        'rmto_code',
        'name_fa',
        'min_weight_kg',
        'max_weight_kg',
        'vehicle_scope',
        'is_international',
        'is_active',
        'association_enabled',
        'is_custom',
        'created_by_user_id',
        'sort_order',
    ];

    protected $casts = [
        'min_weight_kg' => 'integer',
        'max_weight_kg' => 'integer',
        'is_international' => 'boolean',
        'is_active' => 'boolean',
        'association_enabled' => 'boolean',
        'is_custom' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function fleets(): HasMany
    {
        return $this->hasMany(Fleet::class, 'fleet_loading_type_id');
    }

    public function scopeAvailableForCompany($query)
    {
        return $query->where('is_active', true)->where('is_international', true);
    }

    public function scopeAvailableForAssociation($query)
    {
        return $query->where('is_active', true)->where('association_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_custom')->orderBy('sort_order')->orderBy('name_fa');
    }
}

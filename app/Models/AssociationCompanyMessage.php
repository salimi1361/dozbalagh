<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssociationCompanyMessage extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'audience',
        'title',
        'body',
        'category',
        'priority',
        'is_mandatory',
        'is_active',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(AssociationMessageReceipt::class, 'message_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(AssociationSupportTicket::class, 'message_id');
    }

    public function scopeVisibleForCompany($query, int $companyId)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($query) use ($companyId) {
                $query->where('audience', 'all')
                    ->orWhere('company_id', $companyId);
            })
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }
}

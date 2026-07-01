<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'company_code',
        'name',
        'name_fa',
        'name_en',
        'national_id',
        'phone',
        'ceo_mobile',
        'address_fa',
        'address_en',
    ];

    protected static function booted(): void
    {
        static::created(function ($company) {
            if (method_exists($company, 'wallet')) {
                $company->wallet()->create(['balance' => 0.00]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'current_company_id');
    }

    public function fleets(): HasMany
    {
        return $this->hasMany(Fleet::class);
    }
}
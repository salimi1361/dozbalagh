<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Company extends Model
{
    use Notifiable;

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
        'registration_number',
        'ceo_name',
        'ceo_national_code',
        'postal_code',
        'province',
        'city',
        'activity_type',
        'shahbaz_verification_status',
        'shahbaz_review_note',
        'activity_license_number',
        'activity_license_issued_on',
        'activity_license_expires_on',
        'activity_license_status',
    ];

    protected $casts = [
        'shahbaz_verified_at' => 'datetime',
        'activity_license_issued_on' => 'date',
        'activity_license_expires_on' => 'date',
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

    public function associationMessages(): HasMany
    {
        return $this->hasMany(AssociationCompanyMessage::class);
    }

    public function associationTickets(): HasMany
    {
        return $this->hasMany(AssociationSupportTicket::class);
    }

    public function shahbazVerificationHistories(): HasMany
    {
        return $this->hasMany(\App\Shahbaz\Models\CompanyVerificationHistory::class);
    }
}

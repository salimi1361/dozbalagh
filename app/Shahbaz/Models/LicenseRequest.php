<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseRequest extends Model
{
    protected $table = 'shahbaz_license_requests';

    protected $guarded = [];

    protected $casts = [
        'previous_license_expires_on' => 'date',
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function histories(): HasMany { return $this->hasMany(LicenseRequestHistory::class, 'request_id')->latest(); }
}

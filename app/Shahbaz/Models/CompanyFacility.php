<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFacility extends Model
{
    protected $table = 'shahbaz_company_facilities';

    protected $guarded = [];

    protected $casts = [
        'started_on' => 'date',
        'facilities' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}

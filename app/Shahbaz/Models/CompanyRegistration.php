<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRegistration extends Model
{
    protected $table = 'shahbaz_company_registrations';

    protected $guarded = [];

    protected $casts = ['registered_on' => 'date', 'introduction_letter_date' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }
}

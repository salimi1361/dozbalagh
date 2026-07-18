<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CompanyVerificationHistory extends Model
{
    protected $table = 'shahbaz_company_verification_histories';

    protected $guarded = [];

    protected $casts = ['company_snapshot' => 'array', 'checked_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by_user_id'); }
}

<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPermit extends Model
{
    protected $table = 'shahbaz_branch_permits';

    protected $guarded = [];

    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}

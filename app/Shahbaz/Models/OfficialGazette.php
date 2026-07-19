<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialGazette extends Model
{
    protected $table = 'shahbaz_official_gazettes';

    protected $guarded = [];

    protected $casts = ['gazette_date' => 'date', 'notice_date' => 'date'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}

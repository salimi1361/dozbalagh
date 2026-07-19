<?php

namespace App\Shahbaz\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseRequestHistory extends Model
{
    protected $table = 'shahbaz_license_request_histories';

    protected $guarded = [];

    protected $casts = ['request_snapshot' => 'array'];

    public function request(): BelongsTo { return $this->belongsTo(LicenseRequest::class, 'request_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by_user_id'); }
}

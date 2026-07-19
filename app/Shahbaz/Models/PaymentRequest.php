<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $table = 'shahbaz_payment_requests';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function licenseRequest(): BelongsTo { return $this->belongsTo(LicenseRequest::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by_user_id'); }
}

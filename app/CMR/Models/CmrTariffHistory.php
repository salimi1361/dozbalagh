<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrTariffHistory extends Model
{
    protected $table = 'cmr_tariff_history';
    protected $guarded = [];
    protected $casts = [
        'issuance_fee' => 'decimal:2',
        'billing_enabled' => 'boolean',
        'effective_from' => 'datetime',
        'corrected_at' => 'datetime',
    ];
}

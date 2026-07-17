<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmrTariffHistory extends Model
{
    use SoftDeletes;
    protected $table = 'cmr_tariff_history';
    protected $guarded = [];
    protected $casts = [
        'issuance_fee' => 'decimal:2',
        'billing_enabled' => 'boolean',
        'effective_from' => 'datetime',
        'corrected_at' => 'datetime',
    ];
}

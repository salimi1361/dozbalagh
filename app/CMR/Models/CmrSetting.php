<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrSetting extends Model
{
    protected $table = 'cmr_settings';
    protected $guarded = [];
    protected $casts = ['billing_enabled' => 'boolean', 'issuance_fee' => 'decimal:2'];

    public static function current(): self
    {
        return static::firstOrCreate([], ['issuance_fee' => 0, 'currency' => 'IRR', 'billing_enabled' => false]);
    }
}

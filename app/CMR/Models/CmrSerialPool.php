<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrSerialPool extends Model
{
    protected $table = 'cmr_serial_pools';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}

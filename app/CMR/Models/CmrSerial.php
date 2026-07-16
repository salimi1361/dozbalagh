<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrSerial extends Model
{
    protected $table = 'cmr_serials';
    protected $guarded = [];
    protected $casts = ['reserved_at' => 'datetime', 'used_at' => 'datetime'];
}

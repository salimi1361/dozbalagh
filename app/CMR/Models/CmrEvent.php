<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrEvent extends Model
{
    protected $table = 'cmr_events';
    protected $guarded = [];
    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];
}

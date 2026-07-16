<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrAmendment extends Model
{
    protected $guarded = [];
    protected $casts = ['changes' => 'array'];
}

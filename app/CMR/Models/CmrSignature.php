<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrSignature extends Model
{
    protected $guarded = [];
    protected $casts = ['signed_at' => 'datetime'];
}

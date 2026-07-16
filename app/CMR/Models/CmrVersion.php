<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrVersion extends Model
{
    protected $table = 'cmr_versions';
    protected $guarded = [];
    protected $casts = ['snapshot' => 'array'];
}

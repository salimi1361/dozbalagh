<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrNotification extends Model
{
    protected $guarded=[];
    protected $casts=['metadata'=>'array','sent_at'=>'datetime'];
}

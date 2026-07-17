<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrHandoverRecord extends Model
{
    protected $guarded = [];
    protected $casts = ['occurred_at'=>'datetime','latitude'=>'decimal:7','longitude'=>'decimal:7','accuracy'=>'decimal:2'];
    public function attachments() { return $this->hasMany(CmrAttachment::class); }
}

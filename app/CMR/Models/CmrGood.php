<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmrGood extends Model
{
    protected $table = 'cmr_goods';
    protected $guarded = [];
    public function document(): BelongsTo { return $this->belongsTo(CmrDocument::class, 'cmr_document_id'); }
}

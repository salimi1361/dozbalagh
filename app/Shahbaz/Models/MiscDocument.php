<?php

namespace App\Shahbaz\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiscDocument extends Model
{
    protected $table = 'shahbaz_misc_documents';

    protected $guarded = [];

    protected $casts = [
        'file_size' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by_user_id'); }
    public function archiver(): BelongsTo { return $this->belongsTo(User::class, 'archived_by_user_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingVideo extends Model
{
    protected $fillable = [
        'title',
        'description',
        'storage_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

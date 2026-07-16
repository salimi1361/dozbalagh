<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrPrintTemplate extends Model
{
    protected $table = 'cmr_print_templates';
    protected $guarded = [];
    protected $casts = ['field_layout' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
}

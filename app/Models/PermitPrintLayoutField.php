<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitPrintLayoutField extends Model
{
    protected $fillable = ['layout_id', 'field_key', 'label', 'x_mm', 'y_mm', 'width_mm', 'height_mm', 'font_size_pt', 'font_family', 'text_align', 'rotation_deg', 'is_bold', 'show_on_original', 'show_on_copy', 'sort_order'];
    protected $casts = ['is_bold' => 'boolean', 'show_on_original' => 'boolean', 'show_on_copy' => 'boolean'];
}

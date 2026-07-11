<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitPrintLayoutMask extends Model
{
    protected $fillable = ['layout_id', 'label', 'x_mm', 'y_mm', 'width_mm', 'height_mm', 'color'];
}

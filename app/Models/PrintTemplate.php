<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    protected $fillable = [
        'country_name',
        'field_name',
        'pos_x',
        'pos_y',
        'font_size'
    ];
}
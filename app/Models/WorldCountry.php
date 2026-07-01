<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorldCountry extends Model
{
    protected $table = 'world_countries';
    
    protected $fillable = [
        'iso_code',
        'name_fa',
        'name_en'
    ];
}
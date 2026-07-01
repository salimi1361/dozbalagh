<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'amount',
        'ref_number',
        'bank_name',
        'receipt_file',
        'description',
    ];
}
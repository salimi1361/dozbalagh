<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_distributing',
        'default_quota',
        'reject_message',
        'price',
        'allowed_permit_types', // 👈 این را اضافه کردیم
    ];

    protected $casts = [
        'allowed_permit_types' => 'array', // 👈 این هم برای تبدیل JSON به آرایه
    ];
}
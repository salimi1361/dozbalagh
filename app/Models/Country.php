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
        'validity_days', // 🟢 اجازه دسترسی و ذخیره داینامیک تعداد روزهای اعتبار در دیتابیس
        'allowed_permit_types',
    ];

    protected $casts = [
        'allowed_permit_types' => 'array',
    ];
}
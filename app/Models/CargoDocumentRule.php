<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoDocumentRule extends Model
{
    use HasFactory;

    protected $table = 'cargo_document_rules';

    protected $fillable = [
        'cargo_type',
        'fields_config' // ستون جدید حاوی آرایه فیلدها
    ];

    protected $casts = [
        // تبدیل خودکار جی‌سون دیتابیس به آرایه PHP هنگام استفاده در پروژه
        'fields_config' => 'array', 
    ];
}
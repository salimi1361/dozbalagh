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
        'member_price',
        'non_member_price',
        'validity_days', // 🟢 اجازه دسترسی و ذخیره داینامیک تعداد روزهای اعتبار در دیتابیس
        'allowed_permit_types',
        'requestable_permit_types',
    ];

    protected $casts = [
        'price' => 'integer',
        'member_price' => 'integer',
        'non_member_price' => 'integer',
        'allowed_permit_types' => 'array',
        'requestable_permit_types' => 'array',
    ];

    /**
     * مجوزهایی که کاربر می‌تواند در درخواست جدید انتخاب کند.
     * null فقط برای سازگاری با داده‌های پیش از Migration به فهرست انبار fallback می‌شود.
     */
    public function requestablePermitTypes(): array
    {
        return array_values($this->requestable_permit_types ?? $this->allowed_permit_types ?? []);
    }
}

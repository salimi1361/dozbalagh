<?php

namespace App\Models;

// کلاس پایه از Model به Authenticatable تغییر یافت تا قابلیت لاگین مستقل داشته باشد
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Driver extends Authenticatable
{
    // استفاده از تراست‌های ضروری برای تولید توکن (API) و ارسال اعلان
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'user_id',
        'current_company_id',
        'national_code',
        'passport_number',
        'first_name_fa',
        'last_name_fa',
        'first_name_en',
        'last_name_en',
        'contract_status',
        'mobile',         // ضروری برای لاگین مستقل و ارسال پیامک راننده
        'bale_chat_id'    // احراز هویت و ارتباط از طریق بازوی پیام‌رسان بله
    ];

    // ==========================================
    // روابط پایه‌ای سیستم (Dozoole Base Relations)
    // ==========================================

    // رابطه با کاربر پایه سیستم (در صورت وجود حساب کاربری یکپارچه)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // قفل انحصار شرکتی: این راننده در حال حاضر متعلق به کدام شرکت است
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    // دوزبلاغ‌هایی که تا به حال به این راننده تخصیص داده شده است
    public function dozbalaghs(): HasMany
    {
        return $this->hasMany(DozbalaghItem::class);
    }

    // ==========================================
    // روابط مربوط به وب‌اپلیکیشن راننده (Tracking & Flutter Mobile App)
    // ==========================================

    // رویدادهای عملیاتی ثبت شده توسط راننده (مثل اعلام رسیدن به مرز یا مقصد)
    public function events(): HasMany
    {
        return $this->hasMany(DriverEvent::class);
    }

    // تاریخچه موقعیت‌های مکانی (GPS) ارسال شده از گوشی راننده جهت مانیتورینگ مسیر
    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(DriverLocation::class)->latestOfMany('recorded_at');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermitRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'd_code', // 👈 اضافه شدن این فیلد قفل کل دیتابیس را باز می‌کند
        'company_id',
        'driver_id',
        'fleet_id',
        'country_id',
        'permit_type',
        'serial_number',
        'total_amount',
        'payment_status',
        'waybill_file',
        'status',
        'reject_reason',
        'description',
    ];

    // ارتباط با شرکت ثبت‌کننده
    public function company()
    {
        // ... بقیه کدهای خودت بدون تغییر
        return $this->belongsTo(Company::class);
    }

    // ارتباط با راننده
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // ارتباط با ناوگان (کامیون)
    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    // ارتباط با کشور مقصد
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
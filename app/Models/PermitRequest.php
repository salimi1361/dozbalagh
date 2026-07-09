<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermitRequest extends Model
{
    use HasFactory;

    protected $casts = [
        'issued_at' => 'datetime',
        'permit_valid_until' => 'date',
        'cmr_date' => 'date',
        'tir_carnet_date' => 'date',
        'courier_code_sent_at' => 'datetime',
        'company_return_submitted_at' => 'datetime',
        'courier_received_at' => 'datetime',
        'lost_reported_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $fillable = [
        'd_code', // 👈 اضافه شدن این فیلد قفل کل دیتابیس را باز می‌کند
        'company_id',
        'driver_id',
        'fleet_id',
        'country_id',
        'permit_type',
        'serial_number',
        'issued_at',
        'validity_days',
        'permit_valid_until',
        'total_amount',
        'payment_status',
        'waybill_file',
        'status',
        'reject_reason',
        'company_note',
        'description',
        'company_return_image',
        'courier_name',
        'courier_mobile',
        'courier_national_code',
        'courier_vehicle_plate',
        'courier_delivery_code',
        'courier_code_sent_at',
        'company_return_submitted_at',
        'courier_received_at',
        'courier_received_by_user_id',
        'lost_reported_at',
        'lost_reason',
        'closed_at',
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

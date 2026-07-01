<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'balance',
        'blocked_balance', // ستون جدید که اضافه کردیم
    ];

    // موجودی در دسترس (قابل استفاده برای درخواست دوزوله)
    public function getAvailableBalanceAttribute()
    {
        return $this->balance - $this->blocked_balance;
    }

    // ارتباط با شرکت
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // ارتباط با ریز تراکنش‌ها
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
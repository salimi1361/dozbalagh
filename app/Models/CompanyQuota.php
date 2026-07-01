<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyQuota extends Model
{
    use HasFactory;

    protected $table = 'company_quotas';

    // 👈 این بخش بسیار مهم است که دقیقاً همین‌ها باشد
    protected $fillable = [
        'company_id',
        'country_id',
        'custom_limit',
        'used_count',
        'reject_message'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class); // نام مدل شرکت خودت
    }

    public function getEffectiveLimitAttribute()
    {
        if (!is_null($this->custom_limit)) {
            return $this->custom_limit;
        }
        return $this->country->default_quota;
    }

    public function hasAvailableQuota($requestedAmount)
    {
        $limit = $this->effective_limit;
        if (is_null($limit)) {
            return true;
        }
        return ($this->used_count + $requestedAmount) <= $limit;
    }
}
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id',
        'username',
        'password',
        'mobile',
        'status',
        'is_manual'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // رابطه با نقش کاربری
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // اگر کاربر شرکت باشد، اطلاعات تکمیلی آن اینجا قرار می‌گیرد
    public function company(): HasOne
    {
        // 🟢 خطای تایپی $table به $this اصلاح شد
        return $this->hasOne(Company::class); 
    }

    // اگر کاربر راننده باشد، اطلاعات تکمیلی آن اینجا قرار می‌گیرد
    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }
}
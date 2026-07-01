<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    protected $fillable = ['name', 'title_fa', 'parent_id'];

    // رابطه با کاربرانی که این نقش را دارند
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // رابطه با نقش بالادستی (برای سلسله‌مراتب)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    // رابطه با نقش‌های زیرمجموعه
    public function children(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_id');
    }
}
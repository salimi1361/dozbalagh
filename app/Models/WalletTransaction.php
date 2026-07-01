<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'dozbalagh_item_id',
        'amount',
        'type',
        'action_type',
        'transaction_month',
        'description',
    ];

    // ارتباط با کیف پول اصلی
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
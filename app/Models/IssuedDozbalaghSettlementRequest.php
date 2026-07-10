<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedDozbalaghSettlementRequest extends Model
{
    protected $fillable = [
        'period_key', 'period_from', 'period_to', 'issued_count', 'requested_amount',
        'status', 'requested_by_user_id', 'requested_at', 'paid_by_user_id',
        'paid_amount', 'bank_name', 'payment_reference', 'receipt_file', 'paid_at', 'note',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'requested_at' => 'datetime',
        'paid_at' => 'datetime',
        'requested_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssociationMessageReceipt extends Model
{
    protected $fillable = [
        'message_id',
        'company_id',
        'user_id',
        'seen_at',
        'acknowledged_at',
        'acknowledgement_note',
    ];

    protected $casts = [
        'seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AssociationCompanyMessage::class, 'message_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

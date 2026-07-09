<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssociationSupportTicket extends Model
{
    protected $fillable = [
        'company_id',
        'message_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'admin_response',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AssociationCompanyMessage::class, 'message_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssociationTicketMessage::class, 'ticket_id');
    }
}

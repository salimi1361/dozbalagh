<?php

namespace App\CMR\Models;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\PermitRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmrDocument extends Model
{
    use SoftDeletes;

    protected $table = 'cmr_documents';

    protected $guarded = [];

    protected $casts = [
        'issued_at' => 'datetime',
        'taking_over_at' => 'datetime',
        'planned_delivery_at' => 'datetime',
        'issuance_fee' => 'decimal:2',
        'attached_documents' => 'array',
        'successive_carriers' => 'array',
        'charges' => 'array',
        'cash_on_delivery' => 'decimal:2',
        'established_at_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function driver(): BelongsTo { return $this->belongsTo(Driver::class); }
    public function fleet(): BelongsTo { return $this->belongsTo(Fleet::class); }
    public function permitRequest(): BelongsTo { return $this->belongsTo(PermitRequest::class); }
    public function goods(): HasMany { return $this->hasMany(CmrGood::class)->orderBy('line_number'); }
    public function events(): HasMany { return $this->hasMany(CmrEvent::class)->latest('occurred_at'); }
    public function versions(): HasMany { return $this->hasMany(CmrVersion::class)->latest('version'); }
    public function walletEntries(): HasMany { return $this->hasMany(CmrWalletEntry::class); }
    public function amendments(): HasMany { return $this->hasMany(CmrAmendment::class)->latest('to_version'); }
    public function attachments(): HasMany { return $this->hasMany(CmrAttachment::class)->latest(); }
    public function signatures(): HasMany { return $this->hasMany(CmrSignature::class)->latest('signed_at'); }
    public function printTemplate(): BelongsTo { return $this->belongsTo(CmrPrintTemplate::class, 'print_template_id'); }
    public function handovers(): HasMany { return $this->hasMany(CmrHandoverRecord::class)->latest('occurred_at'); }
    public function notifications(): HasMany { return $this->hasMany(CmrNotification::class)->latest(); }
}

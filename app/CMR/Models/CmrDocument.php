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
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function driver(): BelongsTo { return $this->belongsTo(Driver::class); }
    public function fleet(): BelongsTo { return $this->belongsTo(Fleet::class); }
    public function permitRequest(): BelongsTo { return $this->belongsTo(PermitRequest::class); }
    public function goods(): HasMany { return $this->hasMany(CmrGood::class)->orderBy('line_number'); }
    public function events(): HasMany { return $this->hasMany(CmrEvent::class)->latest('occurred_at'); }
    public function versions(): HasMany { return $this->hasMany(CmrVersion::class)->latest('version'); }
    public function walletEntries(): HasMany { return $this->hasMany(CmrWalletEntry::class); }
}

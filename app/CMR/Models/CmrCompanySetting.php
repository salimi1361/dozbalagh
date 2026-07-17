<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrCompanySetting extends Model
{
    protected $table = 'cmr_company_settings';
    protected $guarded = [];
    protected $casts = [
        'require_latin_data'=>'boolean','origin_evidence_enabled'=>'boolean','origin_require_signature'=>'boolean',
        'origin_require_photo'=>'boolean','origin_require_gps'=>'boolean','destination_evidence_enabled'=>'boolean',
        'destination_require_signature'=>'boolean','destination_require_photo'=>'boolean','destination_require_gps'=>'boolean',
        'allow_delivery_exceptions'=>'boolean',
    ];

    public static function forCompany(int $companyId): self
    {
        return static::firstOrCreate(['company_id' => $companyId], [
            'assignment_policy' => 'same_company',
            'serial_mode' => 'pool',
            'print_language' => 'en',
            'require_latin_data' => true,
        ]);
    }
}

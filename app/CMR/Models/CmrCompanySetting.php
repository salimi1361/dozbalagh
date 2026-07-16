<?php

namespace App\CMR\Models;

use Illuminate\Database\Eloquent\Model;

class CmrCompanySetting extends Model
{
    protected $table = 'cmr_company_settings';
    protected $guarded = [];
    protected $casts = ['require_latin_data' => 'boolean'];

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

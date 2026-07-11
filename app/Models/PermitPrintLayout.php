<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitPrintLayout extends Model
{
    protected $fillable = ['country_id', 'permit_type', 'name', 'background_path', 'seal_path', 'signature_path', 'paper_width_mm', 'paper_height_mm', 'orientation', 'offset_x_mm', 'offset_y_mm', 'is_active', 'version'];

    protected $casts = ['is_active' => 'boolean'];

    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function fields(): HasMany { return $this->hasMany(PermitPrintLayoutField::class, 'layout_id')->orderBy('sort_order'); }
    public function masks(): HasMany { return $this->hasMany(PermitPrintLayoutMask::class, 'layout_id'); }
}

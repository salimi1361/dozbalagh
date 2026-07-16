<?php
namespace App\CMR\Models;
use Illuminate\Database\Eloquent\Model;
class CmrGoodsTemplate extends Model { protected $guarded = []; protected $casts = ['last_used_at'=>'datetime','is_active'=>'boolean']; }

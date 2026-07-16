<?php
namespace App\CMR\Models;
use Illuminate\Database\Eloquent\Model;
class CmrParty extends Model { protected $guarded = []; protected $casts = ['last_used_at'=>'datetime','is_active'=>'boolean']; }

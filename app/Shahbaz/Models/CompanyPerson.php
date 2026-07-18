<?php
namespace App\Shahbaz\Models; use App\Models\Company; use App\Models\User; use Illuminate\Database\Eloquent\Model;
class CompanyPerson extends Model { protected $table='shahbaz_company_people'; protected $guarded=[]; protected $casts=['started_on'=>'date','ended_on'=>'date','archived_at'=>'datetime','share_amount'=>'decimal:0','share_percentage'=>'decimal:4']; public function company(){return $this->belongsTo(Company::class);} public function person(){return $this->belongsTo(Person::class);} public function createdBy(){return $this->belongsTo(User::class,'created_by_user_id');} }

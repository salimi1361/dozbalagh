<?php
namespace App\Shahbaz\Models; use Illuminate\Database\Eloquent\Model;
class Person extends Model { protected $table='shahbaz_people'; protected $guarded=[]; protected $casts=['birth_date'=>'date','issued_on'=>'date','is_veteran'=>'boolean']; public function companyRelations(){ return $this->hasMany(CompanyPerson::class); } }

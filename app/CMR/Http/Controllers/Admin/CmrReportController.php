<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrDocument;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class CmrReportController extends Controller
{
    public function index(Request $request){$query=$this->query($request);$summary=['total'=>(clone$query)->count(),'active'=>(clone$query)->whereIn('status',['accepted','in_transit'])->count(),'delivered'=>(clone$query)->whereIn('status',['delivered','finalized'])->count(),'cancelled'=>(clone$query)->where('status','cancelled')->count(),'fees'=>(float)(clone$query)->sum('issuance_fee'),'exceptions'=>(clone$query)->whereHas('handovers',fn($q)=>$q->whereIn('outcome',['partial','damaged','refused']))->count()];return view('CMR.admin.reports',['documents'=>$query->with(['company','driver','fleet','handovers'])->latest('issued_at')->paginate(25)->withQueryString(),'summary'=>$summary,'companies'=>Company::orderBy('name_fa')->get(),'statuses'=>$this->statuses()]);}
    public function export(Request $request){$rows=$this->query($request)->with(['company','driver','fleet','handovers'])->latest('issued_at')->get();return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['شرکت','شماره رسمی','وضعیت','راننده','ناوگان','مبدأ','مقصد','هزینه ریال','مغایرت','تاریخ شمسی']);foreach($rows as$row){$exception=$row->handovers->first(fn($h)=>in_array($h->outcome,['partial','damaged','refused'],true));fputcsv($out,[$row->company?->name_fa,$row->company_serial ?: $row->number,$this->statuses()[$row->status] ?? $row->status,trim(($row->driver?->first_name_fa??'').' '.($row->driver?->last_name_fa??'')),$row->fleet?->transit_plate,$row->taking_over_place,$row->delivery_place,(int)$row->issuance_fee,$exception?->outcome,$row->issued_at ? verta($row->issued_at)->format('Y/m/d H:i') : '']);}fclose($out);},'admin-e-CMR-report-'.now()->format('Y-m-d-H-i').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
    private function query(Request$request){$query=CmrDocument::whereNotNull('issued_at');if($request->filled('company_id'))$query->where('company_id',$request->integer('company_id'));if($request->filled('status'))$query->where('status',$request->string('status')->toString());if($request->boolean('exceptions_only'))$query->whereHas('handovers',fn($q)=>$q->whereIn('outcome',['partial','damaged','refused']));if($from=$this->jalali($request->string('from')->toString(),false))$query->where('issued_at','>=',$from);if($to=$this->jalali($request->string('to')->toString(),true))$query->where('issued_at','<=',$to);return$query;}
    private function jalali(string$value,bool$end){if(blank($value))return null;try{$date=Jalalian::fromFormat('Y/m/d',$value)->toCarbon();return$end?$date->endOfDay():$date->startOfDay();}catch(\Throwable){return null;}}
    private function statuses(){return['issued'=>'صادرشده','accepted'=>'پذیرفته‌شده','in_transit'=>'در مسیر','delivered'=>'تحویل‌شده','finalized'=>'نهایی','cancelled'=>'لغوشده'];}
}

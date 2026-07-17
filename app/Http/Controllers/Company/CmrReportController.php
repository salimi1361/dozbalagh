<?php

namespace App\Http\Controllers\Company;

use App\CMR\Models\CmrDocument;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class CmrReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId=$this->companyId();$query=$this->query($request,$companyId);
        $summary=['total'=>(clone $query)->count(),'issued'=>(clone $query)->where('status','issued')->count(),'active'=>(clone $query)->whereIn('status',['accepted','in_transit'])->count(),'delivered'=>(clone $query)->whereIn('status',['delivered','finalized'])->count(),'cancelled'=>(clone $query)->where('status','cancelled')->count(),'fees'=>(float)(clone $query)->sum('issuance_fee'),'exceptions'=>(clone $query)->whereHas('handovers',fn($q)=>$q->whereIn('outcome',['partial','damaged','refused']))->count()];
        return view('company.cmr-report.index',['documents'=>$query->with(['driver','fleet','handovers'])->latest('issued_at')->paginate(25)->withQueryString(),'summary'=>$summary,'drivers'=>Driver::where('current_company_id',$companyId)->orderBy('last_name_fa')->get(),'fleets'=>Fleet::where('company_id',$companyId)->orderBy('transit_plate')->get(),'statuses'=>$this->statuses()]);
    }

    public function export(Request $request)
    {
        $rows=$this->query($request,$this->companyId())->with(['driver','fleet','handovers'])->latest('issued_at')->get();
        $filename='e-CMR-report-'.now()->format('Y-m-d-H-i').'.csv';
        return response()->streamDownload(function() use($rows){$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['شماره رسمی CMR','وضعیت','راننده','ناوگان','مبدأ','مقصد','مبلغ کسرشده (ریال)','تحویل مبدأ','تحویل مقصد','تاریخ صدور شمسی','تاریخ صدور میلادی']);foreach($rows as $row){$origin=$row->handovers->firstWhere('stage','origin');$destination=$row->handovers->firstWhere('stage','destination');fputcsv($out,[$row->company_serial?:$row->number,$this->statuses()[$row->status]??$row->status,trim(($row->driver?->first_name_fa??'').' '.($row->driver?->last_name_fa??'')),$row->fleet?->transit_plate,$row->taking_over_place,$row->delivery_place,(int)$row->issuance_fee,$origin?->outcome,$destination?->outcome,$row->issued_at ? verta($row->issued_at)->format('Y/m/d H:i') : '',$row->issued_at?->format('Y-m-d H:i')]);}fclose($out);},$filename,['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    private function query(Request $request,int $companyId): Builder
    {
        $query=CmrDocument::query()->where('company_id',$companyId)->whereNotNull('issued_at');
        if($request->filled('status'))$query->where('status',$request->string('status')->toString());
        if($request->filled('driver_id'))$query->where('driver_id',$request->integer('driver_id'));
        if($request->filled('fleet_id'))$query->where('fleet_id',$request->integer('fleet_id'));
        if($request->boolean('exceptions_only'))$query->whereHas('handovers',fn($q)=>$q->whereIn('outcome',['partial','damaged','refused']));
        if($from=$this->jalali($request->string('from')->toString(),false))$query->where('issued_at','>=',$from);
        if($to=$this->jalali($request->string('to')->toString(),true))$query->where('issued_at','<=',$to);
        return $query;
    }

    private function jalali(string $value,bool $end): ?\Carbon\Carbon {if(blank($value))return null;try{$date=Jalalian::fromFormat('Y/m/d',$value)->toCarbon();return $end?$date->endOfDay():$date->startOfDay();}catch(\Throwable){return null;}}
    private function companyId(): int {$id=optional(auth()->user()->company)->id??auth()->user()->company_id??null;abort_unless($id,403);return (int)$id;}
    private function statuses(): array {return ['issued'=>'صادرشده','accepted'=>'پذیرفته‌شده','in_transit'=>'در مسیر','delivered'=>'تحویل‌شده','finalized'=>'نهایی','cancelled'=>'لغوشده'];}
}

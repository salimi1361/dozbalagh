<?php

namespace App\CMR\Http\Controllers\Api;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrSignature;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\CMR\Models\CmrAttachment;
use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrHandoverRecord;
use Illuminate\View\View;

class DriverCmrController extends Controller
{
    public function index(): JsonResponse
    {
        $driverId = (int) auth()->id();
        $documents = CmrDocument::query()
            ->where('driver_id', $driverId)
            ->whereIn('status', ['issued', 'accepted', 'in_transit', 'delivered', 'finalized', 'cancelled'])
            ->with(['company:id,name_fa,name_en', 'fleet:id,transit_plate,smart_card_number,truck_type'])
            ->latest('issued_at')
            ->get()
            ->map(fn (CmrDocument $document) => $this->summary($document));

        return response()->json(['data' => $documents]);
    }

    public function show(CmrDocument $cmr): JsonResponse
    {
        abort_unless((int) $cmr->driver_id === (int) auth()->id(), 403);
        abort_if($cmr->status === 'draft', 404);
        $cmr->load(['company:id,name_fa,name_en,address_en', 'fleet', 'goods', 'events', 'signatures', 'attachments', 'handovers.attachments']);
        $policy=CmrCompanySetting::forCompany((int)$cmr->company_id);

        return response()->json(['data' => $this->summary($cmr) + [
            'consignor' => ['name' => $cmr->consignor_name, 'address' => $cmr->consignor_address, 'country_code' => $cmr->consignor_country_code],
            'consignee' => ['name' => $cmr->consignee_name, 'address' => $cmr->consignee_address, 'country_code' => $cmr->consignee_country_code],
            'carrier' => ['name' => $cmr->carrier_name, 'address' => $cmr->carrier_address, 'country_code' => $cmr->carrier_country_code],
            'goods' => $cmr->goods->map(fn ($good) => [
                'line_number' => $good->line_number,
                'description' => $good->description,
                'marks_and_numbers' => $good->marks_and_numbers,
                'package_type' => $good->package_type,
                'package_count' => $good->package_count,
                'gross_weight_kg' => $good->gross_weight_kg,
                'volume_m3' => $good->volume_m3,
                'un_number' => $good->un_number,
                'adr_class' => $good->adr_class,
            ])->values(),
            'integrity_hash' => $cmr->integrity_hash,
            'version' => $cmr->version,
            'events' => $cmr->events->map(fn($event)=>['type'=>$event->event_type,'description'=>$event->description,'occurred_at'=>$event->occurred_at?->toIso8601String()])->values(),
            'signatures' => $cmr->signatures->map(fn($signature)=>['role'=>$signature->signer_role,'name'=>$signature->signer_name,'signed_at'=>$signature->signed_at?->toIso8601String(),'version'=>$signature->document_version])->values(),
            'attachments' => $cmr->attachments->map(fn($attachment)=>['id'=>$attachment->id,'type'=>$attachment->document_type,'name'=>$attachment->original_name,'sha256'=>$attachment->sha256,'download_url'=>route('api.driver.cmr.attachments.download',[$cmr,$attachment])])->values(),
            'handover_policy'=>$this->handoverPolicy($policy),
            'handovers'=>$cmr->handovers->map(fn($record)=>['id'=>$record->id,'stage'=>$record->stage,'outcome'=>$record->outcome,'signer_name'=>$record->signer_name,'latitude'=>$record->latitude,'longitude'=>$record->longitude,'notes'=>$record->notes,'evidence_hash'=>$record->evidence_hash,'occurred_at'=>$record->occurred_at?->toIso8601String(),'attachments'=>$record->attachments->map(fn($file)=>['id'=>$file->id,'name'=>$file->original_name,'sha256'=>$file->sha256,'download_url'=>route('api.driver.cmr.attachments.download',[$cmr,$file])])->values()])->values(),
        ]]);
    }

    public function handoverRequirements(CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        return response()->json(['data'=>$this->handoverPolicy(CmrCompanySetting::forCompany((int)$cmr->company_id))]);
    }

    public function storeHandover(Request $request, CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        $data=$request->validate([
            'client_uuid'=>['required','uuid'],'stage'=>['required','in:origin,destination'],
            'outcome'=>['required','in:accepted,accepted_with_reservations,delivered,partial,damaged,refused'],
            'signer_name'=>['nullable','string','max:255'],'signer_identifier'=>['nullable','string','max:255'],
            'signature'=>['nullable','image','mimes:png,jpg,jpeg,webp','max:5120'],
            'photos'=>['nullable','array','max:5'],'photos.*'=>['image','mimes:png,jpg,jpeg,webp','max:10240'],
            'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],
            'accuracy'=>['nullable','numeric','min:0','max:10000'],'notes'=>['nullable','string','max:4000'],
            'occurred_at'=>['nullable','date','after_or_equal:'.now()->subDays(7)->toIso8601String(),'before_or_equal:'.now()->addMinutes(10)->toIso8601String()],
            'confirmed'=>['accepted'],
        ]);
        $existing=CmrHandoverRecord::where('cmr_document_id',$cmr->id)->where('stage',$data['stage'])->where('client_uuid',$data['client_uuid'])->first();
        if($existing) return response()->json(['data'=>$existing,'duplicate'=>true]);
        $policy=CmrCompanySetting::forCompany((int)$cmr->company_id); $stage=$data['stage'];
        $enabled=(bool)$policy->{$stage.'_evidence_enabled'};
        if(!$enabled) abort(409,'Evidence workflow is not enabled for this stage.');
        if($policy->{$stage.'_require_signature'} && (!$request->hasFile('signature') || blank($data['signer_name']??null))) abort(422,'Signature and signer name are required.');
        if($policy->{$stage.'_require_photo'} && !$request->hasFile('photos')) abort(422,'At least one evidence photo is required.');
        if($policy->{$stage.'_require_gps'} && (!isset($data['latitude'],$data['longitude']))) abort(422,'GPS location is required.');
        if(!$policy->allow_delivery_exceptions && !in_array($data['outcome'],[$stage==='origin'?'accepted':'delivered'],true)) abort(422,'Delivery exceptions are disabled by company policy.');

        $record=DB::transaction(function() use($request,$cmr,$data,$stage){
            $document=CmrDocument::lockForUpdate()->findOrFail($cmr->id);
            abort_unless($stage==='origin' ? $document->status==='issued' : $document->status==='in_transit',409,'CMR status is not valid for this handover stage.');
            $signaturePath=null;$signatureHash=null;
            if($request->hasFile('signature')){$file=$request->file('signature');$signatureHash=hash_file('sha256',$file->getRealPath());$signaturePath=$file->storeAs('cmr/'.$document->uuid.'/handovers/signatures',Str::uuid().'.'.$file->getClientOriginalExtension(),'local');}
            $occurredAt=isset($data['occurred_at'])?Carbon::parse($data['occurred_at'])->setTimezone(config('app.timezone')):now();
            $evidence=collect($data)->except(['signature','photos'])->merge(['cmr_id'=>$document->id,'version'=>$document->version,'document_hash'=>$document->integrity_hash,'signature_sha256'=>$signatureHash,'driver_id'=>auth()->id(),'occurred_at'=>$occurredAt->toIso8601String()])->all();
            $record=CmrHandoverRecord::create(['cmr_document_id'=>$document->id,'document_version'=>$document->version,'stage'=>$stage,'outcome'=>$data['outcome'],'signer_name'=>$data['signer_name']??null,'signer_identifier'=>$data['signer_identifier']??null,'signature_path'=>$signaturePath,'signature_sha256'=>$signatureHash,'latitude'=>$data['latitude']??null,'longitude'=>$data['longitude']??null,'accuracy'=>$data['accuracy']??null,'notes'=>$data['notes']??null,'client_uuid'=>$data['client_uuid'],'evidence_hash'=>hash('sha256',json_encode($evidence,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),'driver_id'=>auth()->id(),'ip_address'=>$request->ip(),'user_agent'=>mb_substr((string)$request->userAgent(),0,2000),'occurred_at'=>$occurredAt]);
            foreach($request->file('photos',[]) as $photo){$hash=hash_file('sha256',$photo->getRealPath());$path=$photo->storeAs('cmr/'.$document->uuid.'/handovers/photos',Str::uuid().'.'.$photo->getClientOriginalExtension(),'local');CmrAttachment::create(['cmr_document_id'=>$document->id,'cmr_handover_record_id'=>$record->id,'document_type'=>'handover_'.$stage,'original_name'=>$photo->getClientOriginalName(),'storage_path'=>$path,'mime_type'=>$photo->getMimeType()?:'application/octet-stream','size_bytes'=>$photo->getSize(),'sha256'=>$hash,'uploaded_by_driver_id'=>auth()->id()]);}
            $successful=$stage==='origin'?in_array($data['outcome'],['accepted','accepted_with_reservations'],true):in_array($data['outcome'],['delivered','partial','damaged'],true);
            if($successful){
                $driver=auth()->user();$driverName=trim(($driver->first_name_en??'').' '.($driver->last_name_en??''))?:'Driver #'.$driver->id;
                if($stage==='origin'){$this->recordSignature($document,'driver',$driverName,$data['notes']??null,$request,(int)$driver->id,'origin_handover');if(filled($data['signer_name']??null))$this->recordSignature($document,'sender',$data['signer_name'],$data['notes']??null,$request,(int)$driver->id,'witnessed_on_driver_device');}
                elseif(filled($data['signer_name']??null))$this->recordSignature($document,'consignee',$data['signer_name'],$data['notes']??null,$request,(int)$driver->id,'witnessed_on_driver_device');
            }
            $from=$document->status;if($successful)$document->update(['status'=>$stage==='origin'?'accepted':'delivered']);
            $this->event($document,'handover_'.$stage,$from,$document->fresh()->status,'Handover evidence recorded: '.$data['outcome'],(int)auth()->id());
            return $record->load('attachments');
        });
        return response()->json(['data'=>$record],201);
    }

    public function downloadAttachment(CmrDocument $cmr, CmrAttachment $attachment)
    {
        $this->assertOwner($cmr);
        abort_unless((int)$attachment->cmr_document_id===(int)$cmr->id,404);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path),404);
        return Storage::disk('local')->download($attachment->storage_path,$attachment->original_name);
    }

    public function print(CmrDocument $cmr): View
    {
        abort_unless((int) $cmr->driver_id === (int) auth()->id(), 403);
        abort_if($cmr->status === 'draft', 404);
        $cmr->load(['company', 'driver', 'fleet', 'goods', 'signatures', 'printTemplate']);
        if ($cmr->printTemplate?->print_mode === 'preprinted' && $cmr->printTemplate?->background_path) {
            return view('CMR.print.dynamic', compact('cmr'));
        }

        return view('CMR.print.standard', compact('cmr'));
    }

    public function accept(Request $request, CmrDocument $cmr): JsonResponse
    {
        abort_if(CmrCompanySetting::forCompany((int)$cmr->company_id)->origin_evidence_enabled,409,'Use the origin handover evidence workflow.');
        $data = $request->validate(['reservation' => ['nullable', 'string', 'max:2000'], 'confirmed' => ['accepted']]);
        $this->assertOwner($cmr);
        $driver = auth()->user();
        $name = trim(($driver->first_name_en ?? '').' '.($driver->last_name_en ?? '')) ?: 'Driver #'.$driver->id;

        DB::transaction(function () use ($cmr, $data, $request, $name, $driver) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            abort_unless($document->status === 'issued', 409, 'Only an issued CMR can be accepted.');
            $this->recordSignature($document, 'driver', $name, $data['reservation'] ?? null, $request, (int) $driver->id);
            $document->update(['status' => 'accepted', 'carrier_reservations' => $data['reservation'] ?? $document->carrier_reservations]);
            $this->event($document, 'accepted', 'issued', 'accepted', 'Driver accepted the goods and the current CMR version.', (int) $driver->id);
        });
        return response()->json(['message' => 'CMR accepted.', 'status' => 'accepted']);
    }

    public function start(CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        DB::transaction(function () use ($cmr) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            abort_unless($document->status === 'accepted', 409, 'The CMR must first be accepted.');
            $document->update(['status' => 'in_transit']);
            $this->event($document, 'transport_started', 'accepted', 'in_transit', 'International road carriage started.', (int) auth()->id());
        });
        return response()->json(['message' => 'Transport started.', 'status' => 'in_transit']);
    }

    public function deliver(Request $request, CmrDocument $cmr): JsonResponse
    {
        abort_if(CmrCompanySetting::forCompany((int)$cmr->company_id)->destination_evidence_enabled,409,'Use the destination handover evidence workflow.');
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate(['consignee_signer_name' => ['required', 'string', 'max:255', $latin], 'reservation' => ['nullable', 'string', 'max:2000'], 'confirmed_by_consignee' => ['accepted']]);
        $this->assertOwner($cmr);
        DB::transaction(function () use ($cmr, $data, $request) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            abort_unless($document->status === 'in_transit', 409, 'Only a CMR in transit can be delivered.');
            $this->recordSignature($document, 'consignee', $data['consignee_signer_name'], $data['reservation'] ?? null, $request, (int) auth()->id(), 'witnessed_on_driver_device');
            $document->update(['status' => 'delivered']);
            $this->event($document, 'delivered', 'in_transit', 'delivered', 'Goods received and box 24 acknowledgement recorded.', (int) auth()->id());
        });
        return response()->json(['message' => 'Delivery recorded.', 'status' => 'delivered']);
    }

    public function syncLocation(Request $request, CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        abort_unless(in_array($cmr->status, ['accepted','in_transit'], true), 409, 'CMR tracking is not active.');
        $data=$request->validate(['client_uuid'=>['nullable','uuid'],'latitude'=>['required','numeric','between:-90,90'],'longitude'=>['required','numeric','between:-180,180'],'accuracy'=>['nullable','numeric','min:0','max:10000'],'altitude'=>['nullable','numeric','min:-1000','max:20000'],'speed'=>['nullable','numeric','min:0','max:150'],'heading'=>['nullable','numeric','min:0','max:360'],'recorded_at'=>['nullable','date','after_or_equal:'.now()->subDays(7)->toIso8601String(),'before_or_equal:'.now()->addMinutes(10)->toIso8601String()]]);
        auth()->user()->locations()->updateOrCreate(['client_uuid'=>$data['client_uuid']??(string)Str::uuid()], ['dozbalagh_item_id'=>null,'cmr_document_id'=>$cmr->id,'latitude'=>$data['latitude'],'longitude'=>$data['longitude'],'accuracy'=>$data['accuracy']??null,'altitude'=>$data['altitude']??null,'speed'=>$data['speed']??null,'heading'=>$data['heading']??null,'recorded_at'=>isset($data['recorded_at'])?Carbon::parse($data['recorded_at'])->setTimezone(config('app.timezone')):now()]);
        return response()->json(['status'=>'success','accepted'=>1]);
    }

    public function trackingEvent(Request $request, CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        abort_unless(in_array($cmr->status, ['accepted','in_transit'], true), 409, 'CMR tracking is not active.');
        $data=$request->validate(['event_type'=>['required','in:tracking_started,tracking_stopped'],'latitude'=>['nullable','numeric'],'longitude'=>['nullable','numeric']]);
        DB::table('driver_events')->insert(['driver_id'=>auth()->id(),'dozbalagh_item_id'=>null,'cmr_document_id'=>$cmr->id,'event_type'=>$data['event_type'],'latitude'=>$data['latitude']??null,'longitude'=>$data['longitude']??null,'created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['status'=>'success']);
    }

    public function track(CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        $points=DB::table('driver_locations')->where('cmr_document_id',$cmr->id)->orderByDesc('recorded_at')->limit(500)->get(['latitude','longitude','accuracy','altitude','speed','heading','recorded_at'])->reverse()->values();
        return response()->json(['data'=>['cmr_id'=>$cmr->id,'status'=>$cmr->status,'points'=>$points,'latest'=>$points->last()]]);
    }

    private function assertOwner(CmrDocument $cmr): void
    {
        abort_unless((int) $cmr->driver_id === (int) auth()->id(), 403);
    }

    private function handoverPolicy(CmrCompanySetting $settings): array
    {
        return ['origin'=>['enabled'=>$settings->origin_evidence_enabled,'require_signature'=>$settings->origin_require_signature,'require_photo'=>$settings->origin_require_photo,'require_gps'=>$settings->origin_require_gps],'destination'=>['enabled'=>$settings->destination_evidence_enabled,'require_signature'=>$settings->destination_require_signature,'require_photo'=>$settings->destination_require_photo,'require_gps'=>$settings->destination_require_gps],'allow_exceptions'=>$settings->allow_delivery_exceptions];
    }

    private function recordSignature(CmrDocument $document, string $role, string $name, ?string $reservation, Request $request, int $driverId, string $type = 'electronic_acknowledgement'): void
    {
        $signedAt = now();
        $evidence = ['cmr_id' => $document->id, 'version' => $document->version, 'role' => $role, 'name' => $name, 'hash' => $document->integrity_hash, 'signed_at' => $signedAt->toIso8601String(), 'ip' => $request->ip()];
        CmrSignature::updateOrCreate(['cmr_document_id' => $document->id, 'document_version' => $document->version, 'signer_role' => $role], ['signer_name' => $name, 'signature_type' => $type, 'reservation' => $reservation, 'document_hash' => $document->integrity_hash, 'evidence_hash' => hash('sha256', json_encode($evidence, JSON_UNESCAPED_UNICODE)), 'driver_id' => $driverId, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000), 'signed_at' => $signedAt]);
    }

    private function event(CmrDocument $document, string $type, string $from, string $to, string $description, int $driverId): void
    {
        CmrEvent::create(['cmr_document_id' => $document->id, 'event_type' => $type, 'from_status' => $from, 'to_status' => $to, 'actor_driver_id' => $driverId, 'actor_role' => 'driver', 'description' => $description, 'occurred_at' => now()]);
    }

    private function summary(CmrDocument $document): array
    {
        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'number' => $document->number,
            'company_serial' => $document->company_serial,
            'status' => $document->status,
            'company_name' => $document->company?->name_en ?: $document->company?->name_fa,
            'vehicle_plate' => $document->fleet?->transit_plate,
            'taking_over_place' => $document->taking_over_place,
            'taking_over_at' => $document->taking_over_at?->toIso8601String(),
            'delivery_place' => $document->delivery_place,
            'planned_delivery_at' => $document->planned_delivery_at?->toIso8601String(),
            'issued_at' => $document->issued_at?->toIso8601String(),
            'print_url' => route('api.driver.cmr.print', $document),
        ];
    }
}

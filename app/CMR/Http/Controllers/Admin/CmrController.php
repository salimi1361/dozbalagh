<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrPrintTemplate;
use App\CMR\Models\CmrParty;
use App\CMR\Models\CmrLocation;
use App\CMR\Models\CmrGoodsTemplate;
use App\CMR\Models\CmrSetting;
use App\CMR\Models\CmrTariffHistory;
use App\CMR\Services\CmrIssuanceService;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CmrController extends Controller
{
    public function index(Request $request)
    {
        $scope = $request->string('scope')->toString();
        $query = CmrDocument::with('company')->latest();
        if ($scope === 'active') {
            $query->whereIn('status', ['issued', 'accepted', 'in_transit']);
        } elseif ($scope === 'archive') {
            $query->whereIn('status', ['delivered', 'finalized', 'cancelled']);
        } elseif ($scope === 'drafts') {
            $query->where('status', 'draft');
        }
        $documents = $query->paginate(20)->withQueryString();
        return view('CMR.admin.index', compact('documents', 'scope'));
    }

    public function create()
    {
        return $this->formView();
    }

    public function edit(CmrDocument $cmr)
    {
        abort_unless($cmr->status === 'draft', 409, 'Only a draft can be edited.');
        return $this->formView($cmr->load('goods'));
    }

    private function formView(?CmrDocument $editing = null)
    {
        return view('CMR.admin.create', [
            'companies' => Company::orderBy('name')->get(),
            'drivers' => Driver::orderBy('last_name_fa')->get(),
            'fleets' => Fleet::orderBy('id')->get(),
            'masterParties' => CmrParty::where('is_active', true)->orderByDesc('usage_count')->get(),
            'masterLocations' => CmrLocation::where('is_active', true)->orderByDesc('usage_count')->get(),
            'masterGoods' => CmrGoodsTemplate::where('is_active', true)->orderByDesc('usage_count')->get(),
            'editing' => $editing,
        ]);
    }

    public function update(Request $request, CmrDocument $cmr)
    {
        abort_unless($cmr->status === 'draft', 409, 'Only a draft can be edited.');
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate([
            'company_id'=>['required','exists:companies,id'],'driver_id'=>['nullable','exists:drivers,id'],'fleet_id'=>['nullable','exists:fleets,id'],
            'language'=>['required','in:en'],'transport_type'=>['nullable','string','max:100',$latin],
            'consignor_name'=>['required','string','max:255',$latin],'consignor_identifier'=>['nullable','string','max:255'],'consignor_address'=>['nullable','string',$latin],'consignor_country_code'=>['nullable','string','size:2'],
            'consignee_name'=>['required','string','max:255',$latin],'consignee_identifier'=>['nullable','string','max:255'],'consignee_address'=>['nullable','string',$latin],'consignee_country_code'=>['nullable','string','size:2'],
            'carrier_name'=>['required','string','max:255',$latin],'carrier_identifier'=>['nullable','string','max:255'],'carrier_address'=>['nullable','string',$latin],'carrier_country_code'=>['nullable','string','size:2'],
            'taking_over_place'=>['required','string','max:255',$latin],'taking_over_at'=>['nullable','date'],'delivery_place'=>['required','string','max:255',$latin],'planned_delivery_at'=>['nullable','date'],
            'sender_instructions'=>['nullable','string',$latin],'special_agreements'=>['nullable','string',$latin],'carrier_reservations'=>['nullable','string',$latin],
            'carriage_payment'=>['nullable','in:paid,carriage_forward'],'cash_on_delivery'=>['nullable','numeric','min:0'],'established_at_place'=>['nullable','string','max:255',$latin],'established_at_date'=>['nullable','date'],'charges'=>['nullable','array'],
            'attached_documents_text'=>['nullable','string',$latin],'goods'=>['required','array','min:1'],'goods.*.description'=>['required','string','max:255',$latin],
            'goods.*.marks_and_numbers'=>['nullable','string','max:255',$latin],'goods.*.package_type'=>['nullable','string','max:100',$latin],'goods.*.package_count'=>['nullable','numeric','min:0'],'goods.*.gross_weight_kg'=>['nullable','numeric','min:0'],'goods.*.volume_m3'=>['nullable','numeric','min:0'],'goods.*.commodity_code'=>['nullable','string','max:100',$latin],'goods.*.un_number'=>['nullable','string','max:20',$latin],'goods.*.adr_class'=>['nullable','string','max:50',$latin],
        ]);
        DB::transaction(function () use ($cmr, $data) {
            $goods=$data['goods']; unset($data['goods']);
            $data['attached_documents']=collect(preg_split('/\r\n|\r|\n|,/', $data['attached_documents_text']??''))->map(fn($v)=>trim($v))->filter()->values()->all(); unset($data['attached_documents_text']);
            $cmr->update($data); $cmr->goods()->delete();
            foreach($goods as $index=>$good){$cmr->goods()->create($good+['line_number'=>$index+1]);}
            CmrEvent::create(['cmr_document_id'=>$cmr->id,'event_type'=>'draft_updated','from_status'=>'draft','to_status'=>'draft','actor_user_id'=>auth()->id(),'actor_role'=>'admin','description'=>'Draft data updated.','occurred_at'=>now()]);
        });
        return redirect()->route('admin.cmr.show',$cmr)->with('success','پیش‌نویس به‌روزرسانی شد.');
    }

    public function store(Request $request)
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'fleet_id' => ['nullable', 'exists:fleets,id'],
            'language' => ['required', 'in:en,fa,de,fr,tr'],
            'transport_type' => ['nullable', 'string', 'max:100'],
            'company_serial' => ['nullable', 'string', 'max:255'],
            'consignor_name' => ['required', 'string', 'max:255', $latin],
            'consignor_identifier' => ['nullable', 'string', 'max:255'],
            'consignor_address' => ['nullable', 'string', $latin],
            'consignor_country_code' => ['nullable', 'string', 'size:2'],
            'consignee_name' => ['required', 'string', 'max:255', $latin],
            'consignee_identifier' => ['nullable', 'string', 'max:255'],
            'consignee_address' => ['nullable', 'string', $latin],
            'consignee_country_code' => ['nullable', 'string', 'size:2'],
            'carrier_name' => ['required', 'string', 'max:255', $latin],
            'carrier_identifier' => ['nullable', 'string', 'max:255'],
            'carrier_address' => ['nullable', 'string', $latin],
            'carrier_country_code' => ['nullable', 'string', 'size:2'],
            'taking_over_place' => ['required', 'string', 'max:255', $latin],
            'taking_over_at' => ['nullable', 'date'],
            'delivery_place' => ['required', 'string', 'max:255', $latin],
            'planned_delivery_at' => ['nullable', 'date'],
            'sender_instructions' => ['nullable', 'string', $latin],
            'special_agreements' => ['nullable', 'string', $latin],
            'attached_documents_text' => ['nullable', 'string', $latin],
            'carrier_reservations' => ['nullable', 'string', $latin],
            'carriage_payment' => ['nullable', 'in:paid,carriage_forward'],
            'cash_on_delivery' => ['nullable', 'numeric', 'min:0'],
            'established_at_place' => ['nullable', 'string', 'max:255', $latin],
            'established_at_date' => ['nullable', 'date'],
            'charges.freight' => ['nullable', 'numeric', 'min:0'],
            'charges.supplementary' => ['nullable', 'numeric', 'min:0'],
            'charges.customs' => ['nullable', 'numeric', 'min:0'],
            'charges.other' => ['nullable', 'numeric', 'min:0'],
            'goods' => ['required', 'array', 'min:1'],
            'goods.*.description' => ['required', 'string', 'max:255', $latin],
            'goods.*.marks_and_numbers' => ['nullable', 'string', 'max:255', $latin],
            'goods.*.package_type' => ['nullable', 'string', 'max:100', $latin],
            'goods.*.package_count' => ['nullable', 'numeric', 'min:0'],
            'goods.*.gross_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'goods.*.volume_m3' => ['nullable', 'numeric', 'min:0'],
            'goods.*.commodity_code' => ['nullable', 'string', 'max:100', $latin],
            'goods.*.un_number' => ['nullable', 'string', 'max:20', $latin],
            'goods.*.adr_class' => ['nullable', 'string', 'max:50', $latin],
            'save_party' => ['nullable', 'array'],
            'save_party.*' => ['nullable', 'boolean'],
            'save_locations' => ['nullable', 'boolean'],
            'save_goods' => ['nullable', 'boolean'],
        ]);

        $companyId = (int) $data['company_id'];
        if (CmrCompanySetting::forCompany($companyId)->assignment_policy === 'same_company') {
            if (! empty($data['driver_id']) && (int) Driver::findOrFail($data['driver_id'])->company_id !== $companyId) {
                return back()->withInput()->withErrors(['driver_id' => 'راننده باید متعلق به شرکت انتخاب‌شده باشد.']);
            }
            if (! empty($data['fleet_id']) && (int) Fleet::findOrFail($data['fleet_id'])->company_id !== $companyId) {
                return back()->withInput()->withErrors(['fleet_id' => 'ناوگان باید متعلق به شرکت انتخاب‌شده باشد.']);
            }
        }

        $document = DB::transaction(function () use ($data) {
            $goods = $data['goods'];
            $saveParty = $data['save_party'] ?? [];
            $saveLocations = (bool) ($data['save_locations'] ?? false);
            $saveGoods = (bool) ($data['save_goods'] ?? false);
            unset($data['goods'], $data['save_party'], $data['save_locations'], $data['save_goods']);
            $data['attached_documents'] = collect(preg_split('/\r\n|\r|\n|,/', $data['attached_documents_text'] ?? ''))
                ->map(fn ($item) => trim($item))->filter()->values()->all();
            unset($data['attached_documents_text']);
            $data['print_template_id'] = CmrPrintTemplate::where('company_id', $data['company_id'])
                ->where('is_default', true)->where('is_active', true)->value('id');
            $document = CmrDocument::create($data + ['uuid' => (string) Str::uuid(), 'status' => 'draft']);
            foreach ($goods as $index => $good) {
                $document->goods()->create($good + ['line_number' => $index + 1]);
            }
            foreach (['consignor', 'consignee', 'carrier'] as $type) {
                if (! empty($saveParty[$type])) {
                    $party = CmrParty::updateOrCreate(['company_id'=>$document->company_id,'party_type'=>$type,'legal_name'=>$document->{$type.'_name'}], ['identifier'=>$document->{$type.'_identifier'},'address'=>$document->{$type.'_address'},'country_code'=>$document->{$type.'_country_code'},'is_active'=>true]);
                    $party->increment('usage_count'); $party->update(['last_used_at'=>now()]);
                }
            }
            if ($saveLocations) {
                foreach (['taking_over'=>'taking_over_place','delivery'=>'delivery_place'] as $type=>$field) {
                    $location = CmrLocation::updateOrCreate(['company_id'=>$document->company_id,'location_type'=>$type,'name'=>$document->{$field}], ['is_active'=>true]);
                    $location->increment('usage_count'); $location->update(['last_used_at'=>now()]);
                }
            }
            if ($saveGoods) {
                foreach ($goods as $good) {
                    CmrGoodsTemplate::updateOrCreate(['company_id'=>$document->company_id,'name'=>$good['description']], collect($good)->only(['description','package_type','commodity_code','un_number','adr_class'])->all()+['is_active'=>true]);
                }
            }
            CmrEvent::create([
                'cmr_document_id' => $document->id,
                'event_type' => 'created',
                'to_status' => 'draft',
                'actor_user_id' => auth()->id(),
                'actor_role' => 'admin',
                'description' => 'پیش‌نویس CMR ایجاد شد.',
                'occurred_at' => now(),
            ]);
            return $document;
        });

        return redirect()->route('admin.cmr.show', $document)->with('success', 'پیش‌نویس CMR ایجاد شد.');
    }

    public function show(CmrDocument $cmr)
    {
        $cmr->load(['company', 'driver', 'fleet', 'goods', 'events', 'walletEntries', 'versions', 'amendments', 'attachments', 'signatures']);
        return view('CMR.admin.show', compact('cmr'));
    }

    public function print(CmrDocument $cmr)
    {
        $cmr->load(['company', 'driver', 'fleet', 'goods', 'signatures']);
        return view('CMR.print.standard', compact('cmr'));
    }

    public function duplicate(CmrDocument $cmr)
    {
        $cmr->load('goods');
        $document = DB::transaction(function () use ($cmr) {
            $copy = $cmr->replicate([
                'uuid', 'number', 'company_serial', 'status', 'version', 'issued_at', 'issued_by',
                'issuance_fee', 'integrity_hash', 'verification_code', 'finalized_at', 'created_at', 'updated_at',
            ]);
            $copy->uuid = (string) Str::uuid();
            $copy->number = null;
            $copy->company_serial = null;
            $copy->status = 'draft';
            $copy->version = 1;
            $copy->issued_at = null;
            $copy->issued_by = null;
            $copy->issuance_fee = 0;
            $copy->integrity_hash = null;
            $copy->verification_code = null;
            $copy->finalized_at = null;
            $copy->save();
            foreach ($cmr->goods as $good) {
                $copy->goods()->create(collect($good->attributesToArray())->except(['id','cmr_document_id','created_at','updated_at'])->all());
            }
            CmrEvent::create(['cmr_document_id'=>$copy->id,'event_type'=>'duplicated','to_status'=>'draft','actor_user_id'=>auth()->id(),'actor_role'=>'admin','description'=>'Draft copied from CMR #'.$cmr->id,'metadata'=>['source_cmr_id'=>$cmr->id,'source_version'=>$cmr->version],'occurred_at'=>now()]);
            return $copy;
        });
        return redirect()->route('admin.cmr.show',$document)->with('success','یک پیش‌نویس مستقل از سند قبلی ساخته شد؛ شماره رسمی هنگام صدور تخصیص می‌یابد.');
    }

    public function issue(CmrDocument $cmr, CmrIssuanceService $service)
    {
        try {
            $service->issue($cmr, (int) auth()->id());
            return back()->with('success', 'CMR با موفقیت صادر و هزینه آن ثبت شد.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['issue' => $exception->getMessage()]);
        }
    }

    public function cancel(Request $request, CmrDocument $cmr)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        try {
            DB::transaction(function () use ($cmr, $data) {
                $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
                if (! in_array($document->status, ['draft', 'issued'], true)) {
                    throw new \RuntimeException('این وضعیت سند قابل لغو نیست.');
                }

                $fromStatus = $document->status;
                $document->update(['status' => 'cancelled']);
                CmrEvent::create([
                    'cmr_document_id' => $document->id,
                    'event_type' => 'cancelled',
                    'from_status' => $fromStatus,
                    'to_status' => 'cancelled',
                    'actor_user_id' => auth()->id(),
                    'actor_role' => 'admin',
                    'description' => $data['reason'],
                    'metadata' => [
                        'refund_policy' => 'non_refundable',
                        'refunded_amount' => 0,
                        'original_issuance_fee' => (float) $document->issuance_fee,
                    ],
                    'occurred_at' => now(),
                ]);
            });
            return back()->with('success', 'CMR لغو شد. مطابق سیاست مالی، مبلغ صدور مسترد نشد.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['cancel' => $exception->getMessage()]);
        }
    }

    public function settings()
    {
        return view('CMR.admin.settings', [
            'settings' => CmrSetting::current(),
            'history' => CmrTariffHistory::query()->latest('effective_from')->limit(20)->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'issuance_fee' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_enabled' => ['nullable', 'boolean'],
        ]);
        $data['billing_enabled'] = $request->boolean('billing_enabled');
        DB::transaction(function () use ($data) {
            CmrSetting::current()->update($data);
            CmrTariffHistory::create($data + [
                'changed_by' => auth()->id(),
                'effective_from' => now(),
            ]);
        });
        return back()->with('success', 'تنظیمات مالی CMR ذخیره شد.');
    }
}

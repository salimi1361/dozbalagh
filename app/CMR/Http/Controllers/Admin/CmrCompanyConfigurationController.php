<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrPrintTemplate;
use App\CMR\Models\CmrSerialPool;
use App\CMR\Models\CmrSerial;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CmrCompanyConfigurationController extends Controller
{
    public static function printFieldCatalog(): array
    {
        return [
            'company_serial'=>'شماره رسمی CMR','consignor_name'=>'نام فرستنده','consignor_address'=>'نشانی فرستنده',
            'consignee_name'=>'نام گیرنده','consignee_address'=>'نشانی گیرنده','delivery_place'=>'محل تحویل نهایی',
            'taking_over_place'=>'محل تحویل کالا به حمل‌کننده','taking_over_at'=>'تاریخ تحویل کالا','attached_documents'=>'اسناد همراه',
            'goods_table'=>'جدول مشخصات کالا','sender_instructions'=>'دستورهای فرستنده','carrier_name'=>'نام حمل‌کننده',
            'carrier_address'=>'نشانی حمل‌کننده','driver_name'=>'نام لاتین راننده','vehicle_plate'=>'پلاک ناوگان',
            'carrier_reservations'=>'ملاحظات حمل‌کننده','special_agreements'=>'توافق‌های ویژه','charges'=>'هزینه‌ها',
            'established_at'=>'محل و تاریخ تنظیم','sender_signature'=>'امضای فرستنده','carrier_signature'=>'امضای حمل‌کننده',
            'consignee_signature'=>'امضای گیرنده','verification_code'=>'کد اعتبارسنجی',
        ];
    }

    public function index(Request $request)
    {
        $companies = $request->user()?->hasRole('company')
            ? collect([$request->user()->company])->filter()
            : Company::query()->orderBy('name_fa')->get();
        $company = $request->filled('company_id')
            ? $companies->firstWhere('id', (int) $request->integer('company_id'))
            : $companies->first();

        return view('CMR.admin.company-settings', [
            'companies' => $companies,
            'company' => $company,
            'settings' => $company ? CmrCompanySetting::forCompany($company->id) : null,
            'serialPools' => $company ? CmrSerialPool::where('company_id', $company->id)->latest()->get() : collect(),
            'templates' => $company ? CmrPrintTemplate::where('company_id', $company->id)->latest()->get() : collect(),
            'availableSerials' => $company ? CmrSerial::where('company_id', $company->id)->where('status', 'available')->latest()->limit(100)->get() : collect(),
            'availableSerialCount' => $company ? CmrSerial::where('company_id', $company->id)->where('status', 'available')->count() : 0,
            'availablePoolCount' => $company ? (int) CmrSerialPool::where('company_id', $company->id)
                ->where('is_active', true)
                ->selectRaw('COALESCE(SUM(GREATEST(range_end - next_number + 1, 0)), 0) AS remaining')
                ->value('remaining') : 0,
        ]);
    }

    public function updateSettings(Request $request, Company $company)
    {
        $data = $request->validate([
            'assignment_policy' => ['required', 'in:same_company,any_registered,authorized_external'],
            'print_language' => ['required', 'in:en'],
            'require_latin_data' => ['nullable', 'boolean'],
            'origin_evidence_enabled'=>['nullable','boolean'],'origin_require_signature'=>['nullable','boolean'],
            'origin_require_photo'=>['nullable','boolean'],'origin_require_gps'=>['nullable','boolean'],
            'destination_evidence_enabled'=>['nullable','boolean'],'destination_require_signature'=>['nullable','boolean'],
            'destination_require_photo'=>['nullable','boolean'],'destination_require_gps'=>['nullable','boolean'],
            'allow_delivery_exceptions'=>['nullable','boolean'],
        ]);
        $data['require_latin_data'] = true;
        $data['serial_mode'] = 'pool';
        foreach (['origin_evidence_enabled','origin_require_signature','origin_require_photo','origin_require_gps','destination_evidence_enabled','destination_require_signature','destination_require_photo','destination_require_gps','allow_delivery_exceptions'] as $flag) {
            $data[$flag]=$request->boolean($flag);
        }
        CmrCompanySetting::forCompany($company->id)->update($data);
        return back()->with('success', 'سیاست CMR شرکت ذخیره شد.');
    }

    public function storeSerialList(Request $request, Company $company)
    {
        $data = $request->validate(['serials' => ['required', 'string', 'max:20000']]);
        $serials = collect(preg_split('/[\r\n,;]+/', $data['serials']))->map(fn ($value) => trim($value))->filter()->unique();
        if ($serials->isEmpty()) {
            return back()->withErrors(['serials' => 'حداقل یک شماره معتبر وارد کنید.']);
        }
        DB::transaction(function () use ($serials, $company) {
            foreach ($serials as $serial) {
                CmrSerial::firstOrCreate(['company_id' => $company->id, 'serial' => $serial], ['status' => 'available']);
            }
            CmrCompanySetting::forCompany($company->id)->update(['serial_mode' => 'pool']);
        });
        return back()->with('success', $serials->count().' شماره رسمی شرکت ثبت شد.');
    }

    public function storeSerialPool(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:30'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'number_padding' => ['nullable', 'integer', 'min:1', 'max:20'],
            'range_start' => ['required', 'integer', 'min:1'],
            'range_end' => ['required', 'integer', 'gte:range_start'],
        ]);

        $data['number_padding'] = (int) ($data['number_padding'] ?? 1);

        $overlaps = CmrSerialPool::query()
            ->where('company_id', $company->id)
            ->where('prefix', $data['prefix'] ?? null)
            ->where('suffix', $data['suffix'] ?? null)
            ->where('is_active', true)
            ->where('range_start', '<=', $data['range_end'])
            ->where('range_end', '>=', $data['range_start'])
            ->exists();

        if ($overlaps) {
            return back()->withInput()->withErrors([
                'range_start' => 'این بازه با یکی از بازه‌های فعال همین شرکت هم‌پوشانی دارد.',
            ]);
        }

        CmrSerialPool::create($data + [
            'company_id' => $company->id,
            'next_number' => $data['range_start'],
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);
        return back()->with('success', 'بازه سریال CMR شرکت ثبت شد.');
    }

    public function storeTemplate(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'print_mode' => ['required', 'in:full,preprinted'],
            'header_mode' => ['required', 'in:company_profile,custom,none'],
            'custom_company_name' => ['nullable', 'string', 'max:255'],
            'custom_company_address' => ['nullable', 'string', 'max:2000'],
            'custom_company_contact' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        DB::transaction(function () use ($company, $data, $request) {
            $isDefault = $request->boolean('is_default');
            if ($isDefault) {
                CmrPrintTemplate::where('company_id', $company->id)->update(['is_default' => false]);
            }
            CmrPrintTemplate::create($data + [
                'company_id' => $company->id,
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'field_layout' => [],
                'is_default' => $isDefault,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        });
        return back()->with('success', 'قالب چاپ CMR شرکت ثبت شد.');
    }

    public function createTemplate(Company $company)
    {
        return view('CMR.admin.print-template-editor', ['company'=>$company, 'template'=>null, 'catalog'=>self::printFieldCatalog()]);
    }

    public function editTemplate(Company $company, CmrPrintTemplate $template)
    {
        abort_unless((int)$template->company_id === (int)$company->id, 404);
        return view('CMR.admin.print-template-editor', compact('company','template') + ['catalog'=>self::printFieldCatalog()]);
    }

    public function saveTemplate(Request $request, Company $company, ?CmrPrintTemplate $template = null)
    {
        if ($template) abort_unless((int)$template->company_id === (int)$company->id, 404);
        $data=$request->validate([
            'name'=>['required','string','max:255'],'print_mode'=>['required',Rule::in(['full','preprinted'])],
            'header_mode'=>['required',Rule::in(['company_profile','custom','none'])],
            'background'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:15360'],
            'fields_json'=>['required','json'],'is_default'=>['nullable','boolean'],
        ]);
        $fields=json_decode($data['fields_json'],true,512,JSON_THROW_ON_ERROR);
        $catalog=self::printFieldCatalog();
        $fields=collect($fields)->filter(fn($field)=>isset($catalog[$field['field_key']??'']))->map(fn($field)=>[
            'field_key'=>$field['field_key'],'x_mm'=>(float)($field['x_mm']??10),'y_mm'=>(float)($field['y_mm']??10),
            'width_mm'=>(float)($field['width_mm']??50),'height_mm'=>(float)($field['height_mm']??8),
            'font_size_pt'=>(float)($field['font_size_pt']??10),'is_bold'=>(bool)($field['is_bold']??false),
            'text_align'=>$field['text_align']??'left','is_visible'=>(bool)($field['is_visible']??true),
        ])->values()->all();
        DB::transaction(function() use($request,$company,$template,$data,$fields){
            $template ??= new CmrPrintTemplate();
            if($request->hasFile('background')) $data['background_path']=$request->file('background')->store('cmr/print-templates','public');
            $isDefault=$request->boolean('is_default');
            if($isDefault) CmrPrintTemplate::where('company_id',$company->id)->update(['is_default'=>false]);
            $template->fill(collect($data)->except(['background','fields_json'])->merge([
                'company_id'=>$company->id,'paper_size'=>'A4','orientation'=>'portrait','field_layout'=>$fields,
                'is_default'=>$isDefault,'is_active'=>true,'created_by'=>$template->created_by?:auth()->id(),
            ])->all())->save();
        });
        return redirect()->route('admin.cmr.company-settings.index',['company_id'=>$company->id])->with('success','قالب چاپ اختصاصی شرکت ذخیره شد.');
    }
}

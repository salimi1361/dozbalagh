<?php
namespace App\Shahbaz\Http\Controllers;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;
use App\Http\Controllers\Controller; use App\Models\Company; use App\Shahbaz\Models\CompanyVerificationHistory; use App\Shahbaz\Services\CompanyEligibilityService; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
class AssociationVerificationController extends Controller
{
    public function index()
    {
        $companies = Company::whereIn('shahbaz_verification_status', [
            'pending_association_review', 'ready_for_shahbaz_check', 'correction_required',
            'shahbaz_mismatch', 'rejected',
        ])->latest('updated_at')->paginate(25);

        return view('Shahbaz.association.index', compact('companies'));
    }
    public function dossierHome(Company $company, \App\Shahbaz\Services\ShahbazSectionService $sectionService)
    {
        $items = $this->associationDossierItems($company);
        $latest = \App\Shahbaz\Models\DossierReview::where('company_id',$company->id)->latest('id')->get()
            ->unique(fn($review)=>$review->entity_type.':'.$review->entity_id)
            ->keyBy(fn($review)=>$review->entity_type.':'.$review->entity_id);
        $sections = collect($sectionService->rows($company))->filter(fn($row)=>$row['visible'])->map(function($row) use($items,$latest){
            $sectionItems=$items->where('section',$row['key']);
            $states=$sectionItems->map(function($item) use($latest){
                $review=$latest->get($item['type'].':'.$item['id']);
                if($review && $review->reviewed_at->lt($item['updated_at'])) $review=null;
                return $review?->status;
            });
            $row['count']=$sectionItems->count();
            $row['review_status']=$states->contains('correction_required')?'correction_required':($row['count']>0 && $states->every(fn($status)=>$status==='approved')?'approved':'pending');
            return $row;
        });

        return view('Shahbaz.association.dossier.show',compact('company','sections'));
    }
    public function dossierSection(Company $company, string $section)
    {
        $definitions=config('shahbaz_sections',[]);
        abort_unless(array_key_exists($section,$definitions),404);
        $items=$this->associationDossierItems($company)->where('section',$section)->values();
        $latestReviews=\App\Shahbaz\Models\DossierReview::with('reviewer')->where('company_id',$company->id)->latest('id')->get()
            ->unique(fn($review)=>$review->entity_type.':'.$review->entity_id)
            ->keyBy(fn($review)=>$review->entity_type.':'.$review->entity_id);

        return view('Shahbaz.association.dossier.section',[
            'company'=>$company,'sectionKey'=>$section,'sectionLabel'=>$definitions[$section]['label'],
            'items'=>$items,'latestReviews'=>$latestReviews,
        ]);
    }
    private function associationDossierItems(Company $company)
    {
        $items=collect();
        $add=function(string $section,string $type,$model,string $title,array $details) use($items){
            $items->push(['section'=>$section,'type'=>$type,'id'=>$model->id,'title'=>$title,'details'=>$details,'updated_at'=>$model->updated_at]);
        };
        $add('profile','company',$company,'پرونده و مشخصات شرکت',[
            'نام شرکت'=>$company->name_fa,'شناسه ملی'=>$company->national_id,'شماره ثبت'=>$company->registration_number,
            'مدیرعامل'=>$company->ceo_name,'کد ملی مدیرعامل'=>$company->ceo_national_code,'استان و شهر'=>trim($company->province.'، '.$company->city,'، '),
            'کدپستی'=>$company->postal_code,'تلفن'=>$company->phone,'نشانی'=>$company->address_fa,'نوع فعالیت'=>$company->activity_type,
        ]);
        foreach(\App\Shahbaz\Models\LicenseRequest::where('company_id',$company->id)->latest()->get() as $model) $add('requests','license_request',$model,'درخواست '.$model->tracking_code,['نوع درخواست'=>$model->request_type,'حوزه فعالیت'=>$model->activity_scope,'نوع فعالیت'=>$model->activity_type,'وضعیت'=>$model->status,'توضیحات'=>$model->company_description]);
        foreach(\App\Shahbaz\Models\CompanyPerson::with('person')->where('company_id',$company->id)->where('status','!=','archived')->get() as $model) {
            $person=$model->person;
            $details=[
                'تابعیت'=>$person?->nationality,
                'کد ملی'=>$person?->national_code,
                'شماره گذرنامه'=>$person?->passport_number,
                'نام'=>$person?->first_name,
                'نام خانوادگی'=>$person?->last_name,
                'نام پدر'=>$person?->father_name,
                'شماره شناسنامه'=>$person?->birth_certificate_number,
                'تاریخ تولد'=>$person?->birth_date ? verta($person->birth_date)->format('Y/m/d') : null,
                'محل تولد'=>$person?->birth_place,
                'تاریخ صدور شناسنامه'=>$person?->issued_on ? verta($person->issued_on)->format('Y/m/d') : null,
                'شهر صدور'=>$person?->issue_city,
                'جنسیت'=>$person?->gender,
                'شرایط ایثارگری'=>$person?->is_veteran ? 'دارد' : 'ندارد',
            ];
            if($model->relation_type==='personnel') $details['عنوان شغلی']=$model->job_title;
            if($model->relation_type==='board') $details['سمت در هیئت‌مدیره']=$model->board_position;
            if($model->relation_type==='shareholders') $details += [
                'نوع سهامدار'=>$model->shareholder_type,
                'نوع سهام'=>$model->share_type,
                'مبلغ سهام'=>$model->share_amount!==null ? number_format((float)$model->share_amount).' ریال' : null,
                'درصد سهام'=>$model->share_percentage!==null ? rtrim(rtrim(number_format((float)$model->share_percentage,4,'.',''),'0'),'.').'٪' : null,
            ];
            $details += [
                'شروع همکاری'=>$model->started_on ? verta($model->started_on)->format('Y/m/d') : null,
                'پایان همکاری'=>$model->ended_on ? verta($model->ended_on)->format('Y/m/d') : null,
                'توضیحات'=>$model->note,
            ];
            $add($model->relation_type,'company_person',$model,trim($person?->first_name.' '.$person?->last_name),$details);
        }
        foreach(\App\Models\Fleet::where('company_id',$company->id)->latest()->get() as $model) $add('fleet','fleet',$model,'ناوگان '.$model->transit_plate,['پلاک ترانزیت'=>$model->transit_plate,'کارت هوشمند'=>$model->smart_card_number,'نوع وسیله'=>$model->truck_type]);
        if($model=\App\Shahbaz\Models\CompanyFacility::where('company_id',$company->id)->first()) $add('facilities','facility',$model,'محل و امکانات',['نوع محل'=>$model->location_type,'نوع مالکیت'=>$model->ownership_type,'کدپستی'=>$model->postal_code,'تلفن'=>$model->phone,'نشانی'=>$model->address,'امکانات'=>collect($model->facilities ?? [])->map(fn($row)=>($row['type']??'').': '.($row['area']??''))->implode('، ')]);
        foreach(\App\Shahbaz\Models\OfficialGazette::where('company_id',$company->id)->latest('gazette_date')->get() as $model) $add('gazettes','gazette',$model,'روزنامه رسمی '.$model->gazette_number,['تاریخ'=>$model->gazette_date?->format('Y-m-d'),'گروه تغییرات'=>$model->change_group,'شماره آگهی'=>$model->notice_number,'موضوع'=>$model->subject]);
        if($model=\App\Shahbaz\Models\CompanyRegistration::where('company_id',$company->id)->first()) $add('registration','registration',$model,'ثبت شرکت',['تاریخ ثبت'=>$model->registered_on?->format('Y-m-d'),'شهر ثبت'=>$model->registration_city,'نوع حقوقی'=>$model->legal_type,'شماره معرفی‌نامه'=>$model->introduction_letter_number]);
        if(filled($company->activity_license_number)) $items->push(['section'=>'licenses','type'=>'company_license','id'=>$company->id,'title'=>'پروانه اصلی شرکت','details'=>['شماره پروانه'=>$company->activity_license_number,'تاریخ صدور'=>$company->activity_license_issued_on?->format('Y-m-d'),'پایان اعتبار'=>$company->activity_license_expires_on?->format('Y-m-d'),'وضعیت'=>$company->activity_license_status],'updated_at'=>$company->updated_at]);
        foreach(\App\Shahbaz\Models\BranchPermit::where('company_id',$company->id)->latest()->get() as $model) $add('branches','branch',$model,$model->name,['نوع'=>$model->branch_type,'استان'=>$model->province,'شهر'=>$model->city,'نشانی'=>$model->address,'مدیر'=>$model->manager_name,'شماره مجوز'=>$model->permit_number,'پایان اعتبار'=>$model->expires_on?->format('Y-m-d')]);
        foreach(\App\Shahbaz\Models\MiscDocument::where('company_id',$company->id)->where('status','active')->latest()->get() as $model) $add('misc_documents','misc_document',$model,$model->subject,['نام فایل'=>$model->original_name,'توضیحات'=>$model->description,'حجم'=>number_format($model->file_size/1024,1).' KB']);
        return $items;
    }
    public function show(Company $company, CompanyEligibilityService $eligibility)
    {
        $company->load(['shahbazVerificationHistories.changedBy']);
        $licenseRequests = \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)->latest()->get();
        $paymentRequests = \App\Shahbaz\Models\PaymentRequest::with(['licenseRequest', 'approver'])
            ->where('company_id', $company->id)->latest()->get();
        $dossier = [
            'people' => \App\Shahbaz\Models\CompanyPerson::with('person')->where('company_id', $company->id)->where('status', '!=', 'archived')->get()->groupBy('relation_type'),
            'fleets' => \App\Models\Fleet::where('company_id', $company->id)->latest()->get(),
            'facility' => \App\Shahbaz\Models\CompanyFacility::where('company_id', $company->id)->first(),
            'gazettes' => \App\Shahbaz\Models\OfficialGazette::where('company_id', $company->id)->latest('gazette_date')->get(),
            'registration' => \App\Shahbaz\Models\CompanyRegistration::where('company_id', $company->id)->first(),
            'branches' => \App\Shahbaz\Models\BranchPermit::where('company_id', $company->id)->latest()->get(),
            'documents' => \App\Shahbaz\Models\MiscDocument::where('company_id', $company->id)->where('status', 'active')->latest()->get(),
        ];
        $latestReviews = \App\Shahbaz\Models\DossierReview::with('reviewer')->where('company_id', $company->id)
            ->latest('id')->get()->unique(fn ($review) => $review->entity_type.':'.$review->entity_id)
            ->keyBy(fn ($review) => $review->entity_type.':'.$review->entity_id);
        $reviewItems = collect([[
            'section' => 'profile', 'type' => 'company', 'id' => $company->id,
            'title' => 'مشخصات پایه شرکت', 'summary' => $company->national_id.' — '.$company->registration_number, 'updated_at'=>$company->updated_at,
        ]]);
        foreach ($licenseRequests as $item) $reviewItems->push(['section'=>'requests','type'=>'license_request','id'=>$item->id,'title'=>'درخواست '.$item->tracking_code,'summary'=>$item->request_type.' — '.$item->status,'updated_at'=>$item->updated_at]);
        foreach ($dossier['people'] as $type => $relations) foreach ($relations as $item) $reviewItems->push(['section'=>$type,'type'=>'company_person','id'=>$item->id,'title'=>trim(($item->person?->first_name ?? '').' '.($item->person?->last_name ?? '')),'summary'=>$item->job_title ?: ($item->board_position ?: ($item->shareholder_type ?: $type)),'updated_at'=>$item->updated_at]);
        foreach ($dossier['fleets'] as $item) $reviewItems->push(['section'=>'fleet','type'=>'fleet','id'=>$item->id,'title'=>'ناوگان '.$item->transit_plate,'summary'=>$item->truck_type.' — کارت '.$item->smart_card_number,'updated_at'=>$item->updated_at]);
        if ($dossier['facility']) $reviewItems->push(['section'=>'facilities','type'=>'facility','id'=>$dossier['facility']->id,'title'=>'محل و امکانات','summary'=>$dossier['facility']->location_type.' — '.$dossier['facility']->ownership_type,'updated_at'=>$dossier['facility']->updated_at]);
        foreach ($dossier['gazettes'] as $item) $reviewItems->push(['section'=>'gazettes','type'=>'gazette','id'=>$item->id,'title'=>'روزنامه رسمی '.$item->gazette_number,'summary'=>$item->change_group.' — '.$item->subject,'updated_at'=>$item->updated_at]);
        if ($dossier['registration']) $reviewItems->push(['section'=>'registration','type'=>'registration','id'=>$dossier['registration']->id,'title'=>'ثبت شرکت','summary'=>$dossier['registration']->registration_city.' — '.$dossier['registration']->legal_type,'updated_at'=>$dossier['registration']->updated_at]);
        foreach ($dossier['branches'] as $item) $reviewItems->push(['section'=>'branches','type'=>'branch','id'=>$item->id,'title'=>$item->name,'summary'=>$item->branch_type.' — '.$item->permit_number,'updated_at'=>$item->updated_at]);
        foreach ($dossier['documents'] as $item) $reviewItems->push(['section'=>'misc_documents','type'=>'misc_document','id'=>$item->id,'title'=>$item->subject,'summary'=>$item->original_name,'updated_at'=>$item->updated_at]);

        return view('Shahbaz.association.show', compact('company', 'eligibility', 'licenseRequests', 'paymentRequests', 'dossier', 'reviewItems', 'latestReviews'));
    }
    public function downloadDocument(Company $company, \App\Shahbaz\Models\MiscDocument $document)
    {
        abort_unless($document->company_id === $company->id, 403);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }
    public function dossierReview(Request $request, Company $company)
    {
        abort_unless($company->shahbaz_verification_status === 'pending_association_review', 403);
        $data = $request->validate([
            'section_key' => ['required', Rule::in(['profile','requests','personnel','board','shareholders','fleet','facilities','gazettes','registration','licenses','branches','misc_documents'])],
            'entity_type' => ['required', Rule::in(['company','company_license','license_request','company_person','fleet','facility','gazette','registration','branch','misc_document'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['approved','correction_required'])],
            'note' => ['nullable', 'required_if:status,correction_required', 'string', 'max:2000'],
        ]);
        $models = [
            'company'=>Company::class, 'company_license'=>Company::class, 'license_request'=>\App\Shahbaz\Models\LicenseRequest::class,
            'company_person'=>\App\Shahbaz\Models\CompanyPerson::class, 'fleet'=>\App\Models\Fleet::class,
            'facility'=>\App\Shahbaz\Models\CompanyFacility::class, 'gazette'=>\App\Shahbaz\Models\OfficialGazette::class,
            'registration'=>\App\Shahbaz\Models\CompanyRegistration::class, 'branch'=>\App\Shahbaz\Models\BranchPermit::class,
            'misc_document'=>\App\Shahbaz\Models\MiscDocument::class,
        ];
        $entity = $models[$data['entity_type']]::findOrFail($data['entity_id']);
        abort_unless($entity instanceof Company ? $entity->id === $company->id : $entity->company_id === $company->id, 403);
        $expectedSection = match ($data['entity_type']) {
            'company'=>'profile', 'company_license'=>'licenses', 'license_request'=>'requests', 'company_person'=>$entity->relation_type,
            'fleet'=>'fleet', 'facility'=>'facilities', 'gazette'=>'gazettes', 'registration'=>'registration',
            'branch'=>'branches', 'misc_document'=>'misc_documents',
        };
        if ($expectedSection !== $data['section_key']) {
            throw ValidationException::withMessages(['section_key' => 'بخش انتخاب‌شده با نوع رکورد مطابقت ندارد.']);
        }

        \App\Shahbaz\Models\DossierReview::create([
            'company_id'=>$company->id, 'section_key'=>$data['section_key'], 'entity_type'=>$data['entity_type'],
            'entity_id'=>$entity->id, 'status'=>$data['status'], 'note'=>$data['note'] ?? null,
            'entity_snapshot'=>$entity->toArray(), 'reviewed_by_user_id'=>$request->user()->id, 'reviewed_at'=>now(),
        ]);

        return back()->with('success', 'نتیجه کنترل این مورد ثبت شد.');
    }
    public function paymentStore(Request $request, Company $company)
    {
        $data = $request->validate([
            'license_request_id' => [
                'required',
                Rule::exists('shahbaz_license_requests', 'id')->where('company_id', $company->id),
            ],
            'amount' => ['required', 'integer', 'min:10000', 'max:999999999999'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'license_request_id.exists' => 'درخواست انتخاب‌شده متعلق به این شرکت نیست.',
            'amount.min' => 'مبلغ صورتحساب باید حداقل ۱۰٬۰۰۰ ریال باشد.',
        ]);

        $licenseRequest = \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)
            ->findOrFail($data['license_request_id']);

        if (\App\Shahbaz\Models\PaymentRequest::where('license_request_id', $licenseRequest->id)
            ->whereIn('status', ['pending_payment', 'processing', 'paid'])->exists()) {
            return back()->withErrors(['amount' => 'برای این درخواست قبلاً صورتحساب فعال یا پرداخت‌شده ثبت شده است.']);
        }

        \App\Shahbaz\Models\PaymentRequest::create([
            'company_id' => $company->id,
            'license_request_id' => $licenseRequest->id,
            'amount' => $data['amount'],
            'status' => 'pending_payment',
            'description' => $data['description'] ?? null,
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'مبلغ تأیید و صورتحساب برای شرکت صادر شد.');
    }
    public function initialReview(Request $request, Company $company)
    {
        abort_unless($company->shahbaz_verification_status === 'pending_association_review', 403);
        $data = $request->validate([
            'result' => ['required', Rule::in(['initial_approved', 'correction_required', 'rejected'])],
            'description' => ['required', 'string', 'max:3000'],
        ]);
        if ($data['result'] === 'initial_approved') {
            $latest = \App\Shahbaz\Models\DossierReview::where('company_id', $company->id)->latest('id')->get()
                ->unique(fn ($review) => $review->entity_type.':'.$review->entity_id)
                ->keyBy(fn ($review) => $review->entity_type.':'.$review->entity_id);
            $pending = collect($this->reviewEntityStates($company))->filter(function ($updatedAt, $key) use ($latest) {
                $review = $latest->get($key);
                return ! $review || $review->status !== 'approved' || $review->reviewed_at->lt($updatedAt);
            });
            if ($pending->isNotEmpty()) {
                return back()->withErrors(['result' => 'ابتدا تمام موارد پرونده را جداگانه بررسی و تأیید کنید. تعداد باقی‌مانده: '.$pending->count()]);
            }
        }
        $to = match ($data['result']) {
            'initial_approved' => 'ready_for_shahbaz_check',
            'correction_required' => 'correction_required',
            default => 'rejected',
        };

        DB::transaction(function () use ($company, $data, $to, $request): void {
            $from = $company->shahbaz_verification_status;
            $company->forceFill([
                'shahbaz_verification_status' => $to,
                'shahbaz_review_note' => $data['description'],
                'shahbaz_verified_by_user_id' => null,
                'shahbaz_verified_at' => null,
            ])->save();

            \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)
                ->whereIn('status', ['submitted', 'association_review'])
                ->get()->each(function ($licenseRequest) use ($to, $data, $request): void {
                    $requestFrom = $licenseRequest->status;
                    $requestTo = $to === 'ready_for_shahbaz_check' ? 'ready_for_shahbaz_check' : $to;
                    $licenseRequest->forceFill([
                        'status' => $requestTo,
                        'correction_reason' => $to === 'correction_required' ? $data['description'] : null,
                        'finalized_at' => $to === 'rejected' ? now() : null,
                    ])->save();
                    \App\Shahbaz\Models\LicenseRequestHistory::create([
                        'request_id' => $licenseRequest->id,
                        'from_status' => $requestFrom,
                        'to_status' => $requestTo,
                        'description' => $data['description'],
                        'request_snapshot' => $licenseRequest->fresh()->toArray(),
                        'changed_by_user_id' => $request->user()->id,
                    ]);
                });

            CompanyVerificationHistory::create([
                'company_id' => $company->id,
                'from_status' => $from,
                'to_status' => $to,
                'result' => $data['result'],
                'description' => $data['description'],
                'company_snapshot' => $company->fresh()->toArray(),
                'changed_by_user_id' => $request->user()->id,
                'checked_at' => now(),
            ]);
        });

        return redirect()->route('association.shahbaz.companies.index')->with('success', 'نتیجه بررسی اولیه ثبت شد.');
    }
    private function reviewEntityStates(Company $company): array
    {
        $states = ['company:'.$company->id => $company->updated_at];
        if (filled($company->activity_license_number)) $states['company_license:'.$company->id] = $company->updated_at;
        $sources = [
            'license_request' => \App\Shahbaz\Models\LicenseRequest::where('company_id',$company->id)->whereNotIn('status',['cancelled','rejected'])->get(['id','updated_at']),
            'company_person' => \App\Shahbaz\Models\CompanyPerson::where('company_id',$company->id)->where('status','!=','archived')->get(['id','updated_at']),
            'fleet' => \App\Models\Fleet::where('company_id',$company->id)->get(['id','updated_at']),
            'facility' => \App\Shahbaz\Models\CompanyFacility::where('company_id',$company->id)->get(['id','updated_at']),
            'gazette' => \App\Shahbaz\Models\OfficialGazette::where('company_id',$company->id)->get(['id','updated_at']),
            'registration' => \App\Shahbaz\Models\CompanyRegistration::where('company_id',$company->id)->get(['id','updated_at']),
            'branch' => \App\Shahbaz\Models\BranchPermit::where('company_id',$company->id)->get(['id','updated_at']),
            'misc_document' => \App\Shahbaz\Models\MiscDocument::where('company_id',$company->id)->where('status','active')->get(['id','updated_at']),
        ];
        foreach ($sources as $type => $items) foreach ($items as $item) $states[$type.':'.$item->id] = $item->updated_at;

        return $states;
    }
    public function review(Request $request, Company $company, CompanyEligibilityService $eligibility)
    {
        abort_unless($company->shahbaz_verification_status === 'ready_for_shahbaz_check', 403);
        foreach (['activity_license_issued_on_jalali'=>'activity_license_issued_on','activity_license_expires_on_jalali'=>'activity_license_expires_on'] as $jalaliField=>$dateField) {
            if (!$request->filled($jalaliField)) { $request->merge([$dateField=>null]); continue; }
            try { $value=strtr((string)$request->input($jalaliField),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']); $request->merge([$dateField=>Jalalian::fromFormat('Y/m/d',$value)->toCarbon()->format('Y-m-d')]); }
            catch (\Throwable) { throw ValidationException::withMessages([$jalaliField=>'تاریخ شمسی انتخاب‌شده معتبر نیست.']); }
        }
        $data=$request->validate(['result'=>['required',Rule::in(['verified','correction_required','shahbaz_mismatch','company_not_found','follow_up'])],'description'=>['required','string','max:3000'],'activity_license_number'=>['nullable','required_if:result,verified','string','max:100',Rule::unique('companies')->ignore($company->id)],'activity_license_issued_on'=>['nullable','required_if:result,verified','date'],'activity_license_expires_on'=>['nullable','required_if:result,verified','date','after_or_equal:activity_license_issued_on']]);
        if($data['result']==='verified' && $eligibility->missingFields($company)!==[]) return back()->withErrors(['result'=>'تا تکمیل تمام اطلاعات الزامی، تأیید شرکت امکان‌پذیر نیست.']);
        DB::transaction(function() use($company,$data,$request){
            $from=$company->shahbaz_verification_status;
            $verified=$data['result']==='verified';
            $to=$verified ? 'verified' : (in_array($data['result'], ['shahbaz_mismatch','company_not_found'], true) ? 'shahbaz_mismatch' : ($data['result']==='follow_up' ? 'ready_for_shahbaz_check' : $data['result']));
            $company->forceFill(['shahbaz_verification_status'=>$to,'shahbaz_review_note'=>$data['description'],'shahbaz_verified_by_user_id'=>$verified?$request->user()->id:null,'shahbaz_verified_at'=>$verified?now():null,'activity_license_number'=>$verified?$data['activity_license_number']:$company->activity_license_number,'activity_license_issued_on'=>$verified?$data['activity_license_issued_on']:$company->activity_license_issued_on,'activity_license_expires_on'=>$verified?$data['activity_license_expires_on']:$company->activity_license_expires_on,'activity_license_status'=>$verified?'active':'unverified'])->save();
            \App\Shahbaz\Models\LicenseRequest::where('company_id',$company->id)->where('status','ready_for_shahbaz_check')->get()->each(function($licenseRequest) use($verified,$to,$data,$request){
                $requestFrom=$licenseRequest->status;
                $requestTo=$verified?'shahbaz_verified':$to;
                $licenseRequest->forceFill(['status'=>$requestTo,'correction_reason'=>in_array($requestTo,['correction_required','shahbaz_mismatch'],true)?$data['description']:null])->save();
                \App\Shahbaz\Models\LicenseRequestHistory::create(['request_id'=>$licenseRequest->id,'from_status'=>$requestFrom,'to_status'=>$requestTo,'description'=>$data['description'],'request_snapshot'=>$licenseRequest->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id]);
            });
            CompanyVerificationHistory::create(['company_id'=>$company->id,'from_status'=>$from,'to_status'=>$to,'result'=>$data['result'],'description'=>$data['description'],'company_snapshot'=>$company->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id,'checked_at'=>now()]);
        });
        return redirect()->route('association.shahbaz.companies.index')->with('success','نتیجه بررسی شحباز ثبت شد.');
    }
}

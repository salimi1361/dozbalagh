<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FleetLoadingType;
use App\Services\CompanyAssetReleaseGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class FleetController extends Controller 
{
    private function registrationCompanyId(Request $request): ?int
    {
        $user = $request->user();

        if ($user?->hasRole('admin', 'association')) {
            $companyId = $request->integer('company_id');

            return Company::query()
                ->whereKey($companyId)
                ->where('status', 'approved')
                ->value('id');
        }

        return optional($user?->company)->id ?? $user?->company_id;
    }

    private function requiresCompanySelection(Request $request): bool
    {
        return (bool) $request->user()?->hasRole('admin', 'association');
    }

    public function index() {
        // 🟢 خواندن قطعی و امن آیدی شرکت
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $loadingTypes = FleetLoadingType::availableForCompany()->ordered()->get();

        if (!$companyId) {
            return view('company.fleet.index', compact('loadingTypes'))->with(['fleets' => [], 'error' => 'شما به هیچ شرکتی متصل نیستید.']);
        }

        $fleets = DB::table('fleets')
                    ->leftJoin('fleet_loading_types as flt', 'flt.id', '=', 'fleets.fleet_loading_type_id')
                    ->select('fleets.*', 'flt.name_fa as fleet_loading_type_name')
                    ->where('fleets.company_id', $companyId)
                    ->where('fleets.is_active', true)
                    ->orderBy('fleets.id', 'desc')
                    ->get();
        
        foreach ($fleets as $fleet) {
            // 🟢 [جدید] تزریق فیلدهای ترانزیت فرضی برای جلوگیری از خطای وب‌پیج
            if (!isset($fleet->transit_horse)) {
                $fleet->transit_horse = ''; 
            }
            if (!isset($fleet->transit_trailer)) {
                $fleet->transit_trailer = '';
            }

            try {
                // شمارش تعداد دقیق دوزبلاغ‌های فعال برای این پلاک
                $fleet->active_dozbalagh_count = DB::table('dozbalagh_items')
                    ->where('fleet_id', $fleet->id)
                    ->where('company_id', $companyId)
                    ->whereIn('lifecycle_status', ['issued', 'consumed'])
                    ->count();
            } catch (\Exception $e) {
                $fleet->active_dozbalagh_count = 0;
            }
        }

        return view('company.fleet.index', compact('fleets', 'loadingTypes'));
    }

    public function inquireApi(Request $request) {
        try {
            $smartId = $request->input('smart_id');
            if (empty($smartId)) return response()->json(['success' => false, 'message' => 'کارت هوشمند الزامی است.'], 400);
            $smartId = trim((string) $smartId);
            if (!preg_match('/^\d{7}$/', $smartId)) {
                return response()->json(['success' => false, 'message' => 'شماره کارت هوشمند باید دقیقاً ۷ رقم باشد.'], 422);
            }

            $companyId = $this->registrationCompanyId($request);
            if ($this->requiresCompanySelection($request) && ! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'یک شرکت تأییدشده را برای ثبت ناوگان انتخاب کنید.',
                ], 422);
            }
            $local = DB::table('fleets')->where('smart_card_number', $smartId)->first();
            if ($local && ! (bool) ($local->is_active ?? true)) {
                return response()->json(['success' => false, 'message' => 'این ناوگان توسط انجمن غیرفعال شده و امکان استفاده از آن وجود ندارد.'], 403);
            }
            if ($local && $local->company_id !== null && (int) $local->company_id !== (int) $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'این ناوگان در حال حاضر در کارتابل شرکت دیگری فعال است و تا زمان آزادسازی توسط همان شرکت قابل ثبت نیست.',
                ], 409);
            }
            if ($local && (int) $local->company_id === (int) $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'این ناوگان قبلاً در کارتابل شرکت شما ثبت شده است.',
                ], 409);
            }
            if ($local) {
                $localData = array_merge((array) $local, ['can_register' => true]);
                $localData = $this->appendLoadingTypeMetadata($localData);
                return response()->json([
                    'success' => true,
                    'data' => $localData,
                    'message' => 'این ناوگان قبلاً آزاد شده و اکنون قابل ثبت برای شرکت شماست.',
                ]);
            }

            $token = "eyJpdiI6IkhrVE8vVllaWUtHNE9Pb08xLzRSQ1E9PSIsInZhbHVlIjoiSEZSeC9URTVuc01Uc0FYKzBwWWIxTEhTc21ka0tENXFvR2ZYOUJFaFhkZVBHY0RyZ2ZSWkk2OGFOa1V2Y0lUM3NUV3dobmhwdFQzUlRLY2xLRTVnYlpHVE0wRGVuaGc4UzAxOUczQ2x2d0E9IiwibWFjIjoiZGRjMDYzMDBhZDVhY2M5ZmU0NDJmNmM0NDRhN2YxYTBlYjlhMzIyYmM1MWQyMDAzODY1OTBjYTg2OGIzN2VmMCIsInRhZyI6IiJ9";

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post('https://api.bargram.ir/api/v2/ebl/car_inquiry', [
                    'smart_id' => $smartId,
                    'token' => $token
                ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'data' => $this->appendLoadingTypeMetadata($response->json() ?: [])]);
            }
            
            return response()->json([
                'success' => false, 
                'message' => 'پاسخ راهداری (کد ' . $response->status() . '): ' . $response->body()
            ]);

        } catch (Throwable $e) {
            $err = $e->getMessage();
            if (str_contains($err, 'cURL error 28')) {
                return response()->json(['success' => false, 'message' => 'سرور راهداری شلوغ است (تایم‌اوت). دستی وارد کنید.']);
            }
            return response()->json(['success' => false, 'message' => 'خطای شبکه/سیستمی: ' . $err]);
        }
    }

    /**
     * Keep the raw organization response intact while attaching the normalized
     * loading-type record used by the company form.
     */
    private function appendLoadingTypeMetadata(array $payload): array
    {
        $car =& $payload;
        if (isset($payload['data']['car']) && is_array($payload['data']['car'])) {
            $car =& $payload['data']['car'];
        } elseif (isset($payload['car']) && is_array($payload['car'])) {
            $car =& $payload['car'];
        }

        $code = $car['fleet_loading_type_code']
            ?? $car['loading_type_code']
            ?? $car['LoadingTypeCode']
            ?? $car['BARGIR_ID']
            ?? $car['bargir_code']
            ?? null;
        $existingTypeId = $car['fleet_loading_type_id'] ?? null;
        $type = $existingTypeId ? FleetLoadingType::find((int) $existingTypeId) : null;
        if (!$type && $code !== null && $code !== '') {
            $type = FleetLoadingType::where('rmto_code', (string) $code)->first();
        }

        $car['fleet_loading_type_id'] = $type?->id;
        $car['fleet_loading_type_code'] = $type?->rmto_code ?: ($code !== null ? (string) $code : null);
        $car['fleet_loading_type_name'] = $type?->name_fa
            ?: ($car['LoadingTypeTitle'] ?? $car['bargir_name'] ?? $car['truck_type'] ?? null);
        $car['fleet_loading_type_allowed'] = (bool) ($type?->is_active && $type?->is_international);

        return $payload;
    }

    public function store(Request $request) {
        try {
            $companyId = $this->registrationCompanyId($request);
            if ($this->requiresCompanySelection($request) && ! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'یک شرکت تأییدشده را برای ثبت ناوگان انتخاب کنید.',
                ], 422);
            }
            
            if (!$companyId) {
                return response()->json(['success' => false, 'message' => 'حساب کاربری شما به هیچ شرکتی متصل نیست.'], 400);
            }

            $isEdit = $request->boolean('is_edit');
            // شماره‌های ترانزیت باید فقط با حروف انگلیسی بزرگ، عدد لاتین و خط تیره ذخیره شوند.
            // حروف کوچک به بزرگ تبدیل می‌شوند؛ کاراکترهای فارسی یا سایر کاراکترها رد می‌شوند.
            $transitHorse = strtoupper(trim((string) $request->input('transit_horse')));
            $transitTrailer = strtoupper(trim((string) $request->input('transit_trailer')));
            $validator = Validator::make([
                'smart_id'        => trim((string) $request->input('smart_id')),
                'loading_type'    => trim((string) $request->input('loading_type')),
                'loading_type_id' => $request->input('loading_type_id'),
                'loading_type_code' => trim((string) $request->input('loading_type_code')),
                'loading_type_source' => trim((string) $request->input('loading_type_source', 'manual')),
                'organization_payload' => $request->input('organization_payload'),
                'transit_horse'   => $transitHorse,
                'transit_trailer' => $transitTrailer,
                'transit_plate'   => trim((string) $request->input('transit_plate')),
            ], [
                'smart_id'        => ['required', 'string', 'digits:7'],
                'loading_type'    => ['nullable', 'string', 'max:255'],
                'loading_type_id' => [$isEdit ? 'nullable' : 'required', 'nullable', 'integer', Rule::exists('fleet_loading_types', 'id')],
                'loading_type_code' => ['nullable', 'string', 'max:50'],
                'loading_type_source' => ['nullable', Rule::in(['rmto', 'manual'])],
                'organization_payload' => ['nullable', 'json', 'max:30000'],
                'transit_horse'   => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/'],
                'transit_trailer' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/'],
                'transit_plate'   => ['required', 'string', 'max:80', 'regex:/^[^-]+-[^-]+-[^-]+-[^-]+$/u'],
            ], [
                'smart_id.required'        => 'شماره کارت هوشمند الزامی است.',
                'smart_id.digits'          => 'شماره کارت هوشمند باید دقیقاً ۷ رقم باشد.',
                'loading_type_id.required' => 'انتخاب نوع بارگیر (یدک) الزامی است.',
                'loading_type_id.exists'   => 'نوع بارگیر انتخاب‌شده معتبر نیست.',
                'loading_type_source.in'  => 'منبع نوع بارگیر معتبر نیست.',
                'organization_payload.json' => 'اطلاعات استعلام سازمان معتبر نیست.',
                'transit_horse.required'   => 'شماره ترانزیت اسب (کشنده) الزامی است.',
                'transit_trailer.required' => 'شماره ترانزیت یدک (بارگیر) الزامی است.',
                'transit_horse.regex'     => 'شماره ترانزیت اسب فقط باید شامل حروف انگلیسی بزرگ، عدد لاتین و خط تیره باشد.',
                'transit_trailer.regex'   => 'شماره ترانزیت یدک فقط باید شامل حروف انگلیسی بزرگ، عدد لاتین و خط تیره باشد.',
                'transit_plate.required'   => 'شماره پلاک الزامی است.',
                'transit_plate.regex'      => 'ساختار پلاک کامل نیست؛ هر چهار بخش پلاک را وارد کنید.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $transitPlate = $request->transit_plate;
            
            if (empty($transitPlate)) {
                return response()->json(['success' => false, 'message' => 'پلاک ترانزیت الزامی است.'], 400);
            }

            // بررسی جلوگیری از تداخل (اگر ناوگان دست شرکت دیگری باشد)
            if (!$isEdit) {
                $existingFleet = DB::table('fleets')->where('transit_plate', $transitPlate)->first();
                if ($existingFleet && ! (bool) ($existingFleet->is_active ?? true)) {
                    return response()->json(['success' => false, 'message' => 'این ناوگان توسط انجمن غیرفعال شده و امکان ثبت یا ویرایش آن وجود ندارد.'], 403);
                }
                if ($existingFleet && $existingFleet->company_id != null && $existingFleet->company_id != $companyId) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'خطا: این ناوگان در حال حاضر در کارتابل یک شرکت دیگر فعال است.'
                    ], 400);
                }
            }

            $existingFleet = DB::table('fleets')->where('transit_plate', $transitPlate)->first();
            if ($existingFleet && ! (bool) ($existingFleet->is_active ?? true)) {
                return response()->json(['success' => false, 'message' => 'این ناوگان توسط انجمن غیرفعال شده و امکان ثبت یا ویرایش آن وجود ندارد.'], 403);
            }

            if ($existingFleet && $existingFleet->company_id !== null
                && (int) $existingFleet->company_id !== (int) $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'این ناوگان در حال حاضر در کارتابل شرکت دیگری فعال است و ابتدا باید توسط همان شرکت آزاد شود.',
                ], 409);
            }

            $loadingType = null;
            $loadingTypeId = $request->input('loading_type_id');
            if ($loadingTypeId !== null && $loadingTypeId !== '') {
                $loadingType = FleetLoadingType::find((int) $loadingTypeId);
                if (!$loadingType || !$loadingType->is_active || !$loadingType->is_international) {
                    return response()->json([
                        'success' => false,
                        'message' => 'این نوع بارگیر برای ناوگان بین‌المللی فعال نیست؛ از گزینه‌های مجاز انتخاب کنید.',
                    ], 422);
                }
            } elseif ($isEdit && $existingFleet && !empty($existingFleet->fleet_loading_type_id)) {
                $loadingType = FleetLoadingType::find((int) $existingFleet->fleet_loading_type_id);
                if ($loadingType && (!$loadingType->is_active || !$loadingType->is_international)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'نوع بارگیر فعلی دیگر برای ناوگان بین‌المللی فعال نیست؛ آن را اصلاح کنید.',
                    ], 422);
                }
                $loadingTypeId = $loadingType?->id;
            }

            if (!$isEdit && !$loadingType) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع بارگیر (یدک) را از فهرست گزینه‌های مجاز انتخاب کنید.',
                ], 422);
            }

            $organizationPayload = null;
            $rawOrganizationPayload = trim((string) $request->input('organization_payload', ''));
            if ($rawOrganizationPayload !== '') {
                $decodedOrganizationPayload = json_decode($rawOrganizationPayload, true);
                if (is_array($decodedOrganizationPayload)) {
                    $organizationPayload = $decodedOrganizationPayload;
                }
            }
            $loadingTypeSource = $request->input('loading_type_source') === 'rmto' ? 'rmto' : 'manual';
            $loadingTypeName = $loadingType?->name_fa ?: trim((string) $request->input('loading_type'));
            $loadingTypeCode = $loadingType?->rmto_code ?: trim((string) $request->input('loading_type_code')) ?: null;

            // 🟢 ثبت قطعی و کامل اطلاعات فرم به همراه شماره ترانزیت اسب و یدک
            $fleetData = [
                    'company_id'        => $companyId,
                    'smart_card_number' => $request->smart_id,
                    'truck_type'        => $loadingTypeName,
                    'fleet_loading_type_id' => $loadingTypeId ?: ($existingFleet->fleet_loading_type_id ?? null),
                    'loading_type_code' => $loadingTypeCode,
                    'loading_type_source' => $loadingTypeSource,
                    'organization_payload' => $organizationPayload,
                    'is_manual'         => $loadingTypeSource !== 'rmto',
                    'transit_horse'     => $transitHorse,
                    'transit_trailer'   => $transitTrailer,
                    'updated_at'        => now(),
                ];

            if ($existingFleet) {
                $updated = DB::table('fleets')
                    ->where('id', $existingFleet->id)
                    ->where(function ($query) use ($companyId) {
                        $query->whereNull('company_id')->orWhere('company_id', $companyId);
                    })
                    ->update($fleetData);

                if ($updated === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'مالکیت ناوگان هم‌زمان تغییر کرده است؛ صفحه را تازه‌سازی و دوباره بررسی کنید.',
                    ], 409);
                }
            } else {
                DB::table('fleets')->insert($fleetData + [
                    'transit_plate' => $transitPlate,
                    'created_at' => now(),
                ]);
            }
            
            return response()->json(['success' => true, 'message' => 'اطلاعات ناوگان با موفقیت در کارتابل شرکت شما ذخیره شد.']);
            
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false, 
                'message' => 'خطایی در ثبت ناوگان رخ داد.'
            ], 500);
        }
    }

    public function release(Request $request, CompanyAssetReleaseGuard $releaseGuard) {
        try {
            $companyId = optional(auth()->user()->company)->id ?? auth()->user()->company_id ?? null;
            $fleet = DB::table('fleets')->where('transit_plate', $request->transit_plate)->first();
            if (!$fleet) {
                return response()->json(['success' => false, 'message' => 'ناوگان یافت نشد.'], 404);
            }

            if ($fleet && ! (bool) ($fleet->is_active ?? true)) {
                return response()->json(['success' => false, 'message' => 'این ناوگان توسط انجمن غیرفعال شده و قابل تغییر نیست.'], 403);
            }

            if (!$companyId || (int) $fleet->company_id !== (int) $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'این ناوگان متعلق به شرکت شما نیست و امکان آزادسازی آن را ندارید.',
                ], 403);
            }

            $activeCount = $releaseGuard->activePermitsForFleet((int) $fleet->id);

            if ($activeCount > 0) {
                return response()->json(['success' => false, 'message' => "این ناوگان در حال حاضر {$activeCount} دوزبلاغ فعال دارد و آزادسازی آن غیرمجاز است."], 403);
            }

            DB::table('fleets')
                ->where('id', $fleet->id)
                ->where('company_id', $companyId)
                ->update(['company_id' => null]);
            return response()->json(['success' => true, 'message' => 'ناوگان از شرکت شما آزاد شد و در سامانه مرکزی باقی ماند.']);
            
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'خطایی در آزادسازی ناوگان رخ داد.'], 500);
        }
    }
}

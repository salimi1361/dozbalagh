<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\LicenseRequest;
use App\Shahbaz\Models\LicenseRequestHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyLicenseRequestController extends Controller
{
    public const TYPES = [
        'initial' => 'صدور اولیه پروانه',
        'renewal' => 'تمدید پروانه',
        'details_correction' => 'اصلاح مشخصات',
        'duplicate' => 'صدور المثنی',
        'ceo_change' => 'تغییر مدیرعامل',
        'address_or_branch_change' => 'تغییر آدرس یا شعبه',
        'activity_type_change' => 'تغییر نوع فعالیت',
    ];

    public function index(Request $request)
    {
        $company = $request->user()->company;
        $items = LicenseRequest::where('company_id', $company->id)->latest()->get();

        return view('Shahbaz.company.requests.index', [
            'company' => $company,
            'items' => $items,
            'types' => self::TYPES,
            'editable' => $this->dossierEditable($company),
        ]);
    }

    public function create(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($this->dossierEditable($company), 403);

        return view('Shahbaz.company.requests.form', [
            'item' => new LicenseRequest,
            'company' => $company,
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($this->dossierEditable($company), 403);
        $data = $this->validated($request, $company);

        $item = DB::transaction(function () use ($data, $company, $request) {
            $item = LicenseRequest::create($data + [
                'company_id' => $company->id,
                'tracking_code' => $this->trackingCode(),
                'status' => 'draft',
                'created_by_user_id' => $request->user()->id,
            ]);
            $this->history($item, null, 'draft', 'درخواست توسط شرکت ایجاد شد.', $request->user()->id);
            return $item;
        });

        return redirect()->route('company.shahbaz.requests.index')->with('success', 'درخواست با کد رهگیری '.$item->tracking_code.' ثبت شد.');
    }

    public function edit(Request $request, LicenseRequest $licenseRequest)
    {
        $this->owned($request, $licenseRequest);
        abort_unless($this->canEdit($request, $licenseRequest), 403);

        return view('Shahbaz.company.requests.form', [
            'item' => $licenseRequest,
            'company' => $request->user()->company,
            'types' => self::TYPES,
        ]);
    }

    public function update(Request $request, LicenseRequest $licenseRequest)
    {
        $this->owned($request, $licenseRequest);
        abort_unless($this->canEdit($request, $licenseRequest), 403);
        $data = $this->validated($request, $request->user()->company);

        DB::transaction(function () use ($licenseRequest, $data, $request) {
            $from = $licenseRequest->status;
            $licenseRequest->update($data + ['status' => 'draft', 'correction_reason' => null]);
            $this->history($licenseRequest->fresh(), $from, 'draft', 'اطلاعات درخواست توسط شرکت ویرایش شد.', $request->user()->id);
        });

        return redirect()->route('company.shahbaz.requests.index')->with('success', 'درخواست به‌روزرسانی شد.');
    }

    private function validated(Request $request, $company): array
    {
        $data = $request->validate([
            'request_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'activity_scope' => ['required', Rule::in(['domestic', 'international', 'both'])],
            'activity_type' => ['required', 'string', 'max:100'],
            'company_description' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($data['request_type'] === 'renewal') {
            abort_unless(filled($company->activity_license_number) && filled($company->activity_license_expires_on), 422, 'تمدید فقط برای پروانه قبلی ثبت‌شده مجاز است.');
            $data['previous_license_number'] = $company->activity_license_number;
            $data['previous_license_expires_on'] = $company->activity_license_expires_on;
        }

        return $data;
    }

    private function history(LicenseRequest $item, ?string $from, string $to, string $description, ?int $userId): void
    {
        LicenseRequestHistory::create([
            'request_id' => $item->id,
            'from_status' => $from,
            'to_status' => $to,
            'description' => $description,
            'request_snapshot' => $item->toArray(),
            'changed_by_user_id' => $userId,
        ]);
    }

    private function trackingCode(): string
    {
        do { $code = 'SH-'.now()->format('ymd').'-'.Str::upper(Str::random(6)); }
        while (LicenseRequest::where('tracking_code', $code)->exists());
        return $code;
    }

    private function dossierEditable($company): bool
    {
        return in_array($company->shahbaz_verification_status, ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'], true);
    }

    private function canEdit(Request $request, LicenseRequest $item): bool
    {
        return $this->dossierEditable($request->user()->company)
            && in_array($item->status, ['draft', 'correction_required', 'shahbaz_mismatch'], true);
    }

    private function owned(Request $request, LicenseRequest $item): void
    {
        abort_unless($item->company_id === $request->user()->company->id, 404);
    }
}

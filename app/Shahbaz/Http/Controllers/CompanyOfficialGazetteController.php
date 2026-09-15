<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\OfficialGazette;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyOfficialGazetteController extends Controller
{
    private const EDITABLE_STATUSES = ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'];

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $gazettes = $this->filteredGazettes($company->id, $dateFrom, $dateTo)->get();

        return view('Shahbaz.company.gazettes.index', [
            'company' => $company,
            'gazettes' => $gazettes,
            'editable' => in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true),
            'changeGroups' => $this->changeGroups(),
            'dateFromJalali' => $request->input('date_from_jalali'),
            'dateToJalali' => $request->input('date_to_jalali'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $company = $request->user()->company;
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $gazettes = $this->filteredGazettes($company->id, $dateFrom, $dateTo)->get();
        $fileName = 'official-gazettes-'.$company->national_id.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($company, $gazettes, $request): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['گزارش روزنامه رسمی شرکت', $company->name_fa]);
            fputcsv($output, ['شناسه ملی', $company->national_id]);
            fputcsv($output, ['از تاریخ شمسی', $request->input('date_from_jalali') ?: 'ابتدای سوابق', 'تا تاریخ شمسی', $request->input('date_to_jalali') ?: 'انتهای سوابق']);
            fputcsv($output, []);
            fputcsv($output, ['ردیف', 'شماره روزنامه رسمی', 'تاریخ روزنامه رسمی (شمسی)', 'گروه تغییرات', 'شماره نامه/اعلامیه', 'تاریخ نامه (شمسی)', 'موضوع آگهی', 'توضیحات']);

            foreach ($gazettes as $index => $gazette) {
                fputcsv($output, [
                    $index + 1,
                    $gazette->gazette_number,
                    $gazette->gazette_date ? verta($gazette->gazette_date)->format('Y/m/d') : '',
                    $gazette->change_group,
                    $gazette->notice_number,
                    $gazette->notice_date ? verta($gazette->notice_date)->format('Y/m/d') : '',
                    $gazette->subject,
                    $gazette->notes,
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true), 403);
        $this->mergeJalaliDate($request, 'gazette_date_jalali', 'gazette_date', true);
        $this->mergeJalaliDate($request, 'notice_date_jalali', 'notice_date');

        $data = $request->validate([
            'gazette_number' => ['required', 'string', 'max:50', Rule::unique('shahbaz_official_gazettes')->where('company_id', $company->id)],
            'gazette_date' => ['required', 'date'],
            'change_group' => ['required', Rule::in($this->changeGroups())],
            'notice_number' => ['nullable', 'string', 'max:100'],
            'notice_date' => ['nullable', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'gazette_number.unique' => 'این شماره روزنامه رسمی قبلاً برای شرکت ثبت شده است.',
        ]);

        OfficialGazette::create($data + [
            'company_id' => $company->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'سابقه روزنامه رسمی ثبت شد. سوابق قبلی بدون تغییر نگهداری می‌شوند.');
    }

    private function changeGroups(): array
    {
        return ['تأسیس شرکت', 'تغییر هیئت‌مدیره', 'تغییر مدیرعامل', 'تغییر سهامداران', 'تغییر نشانی', 'تغییر نام شرکت', 'تغییر موضوع فعالیت', 'تغییر سرمایه', 'سایر تغییرات'];
    }

    private function mergeJalaliDate(Request $request, string $source, string $target, bool $required = false): void
    {
        if (! $request->filled($source)) {
            $request->merge([$target => null]);
            if ($required) throw ValidationException::withMessages([$source => 'انتخاب تاریخ شمسی الزامی است.']);
            return;
        }

        try {
            $value = strtr((string) $request->input($source), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
            $request->merge([$target => Jalalian::fromFormat('Y/m/d', $value)->toCarbon()->format('Y-m-d')]);
        } catch (\Throwable) {
            throw ValidationException::withMessages([$source => 'تاریخ شمسی انتخاب‌شده معتبر نیست.']);
        }
    }

    private function dateRange(Request $request): array
    {
        $from = $this->parseJalaliDate($request->input('date_from_jalali'), 'date_from_jalali');
        $to = $this->parseJalaliDate($request->input('date_to_jalali'), 'date_to_jalali');

        if ($from && $to && $from->gt($to)) {
            throw ValidationException::withMessages(['date_to_jalali' => 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.']);
        }

        return [$from, $to];
    }

    private function parseJalaliDate(mixed $value, string $field): ?\Carbon\Carbon
    {
        if (blank($value)) return null;

        try {
            $normalized = strtr(trim((string) $value), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
            return Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon()->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'تاریخ شمسی انتخاب‌شده معتبر نیست.']);
        }
    }

    private function filteredGazettes(int $companyId, ?\Carbon\Carbon $dateFrom, ?\Carbon\Carbon $dateTo)
    {
        return OfficialGazette::query()
            ->where('company_id', $companyId)
            ->when($dateFrom, fn ($query) => $query->whereDate('gazette_date', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($query) => $query->whereDate('gazette_date', '<=', $dateTo->toDateString()))
            ->latest('gazette_date')
            ->latest('id');
    }
}

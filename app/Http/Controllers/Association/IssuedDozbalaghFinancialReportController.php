<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\IssuedDozbalaghSettlementRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class IssuedDozbalaghFinancialReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $companyId, $fromJalali, $toJalali] = $this->filters($request);
        $query = $this->reportQuery($from, $to, $companyId);
        $period = $this->settlementTargetPeriod();
        $currentSettlement = IssuedDozbalaghSettlementRequest::where('period_key', $period['key'])->first();

        return view('association.issued-financial.index', [
            'rows' => (clone $query)->paginate(25)->withQueryString(),
            'totalAmount' => (clone $query)->sum('item.price'),
            'totalCount' => (clone $query)->count(),
            'companies' => Company::query()->orderBy('name_fa')->get(['id', 'name_fa', 'name']),
            'dateFromJalali' => $fromJalali,
            'dateToJalali' => $toJalali,
            'companyId' => $companyId,
            'settlements' => IssuedDozbalaghSettlementRequest::latest('id')->paginate(12, ['*'], 'settlements_page'),
            'currentSettlement' => $currentSettlement,
            'settlementWindowOpen' => (int) Jalalian::now()->getDay() <= 5,
            'currentPeriod' => $period,
            'isAdmin' => auth()->user()?->role?->name === 'admin',
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to, $companyId, $fromJalali, $toJalali] = $this->filters($request);
        $rows = $this->reportQuery($from, $to, $companyId)->get();
        $total = $rows->sum(fn ($row) => (float) $row->deducted_amount);

        return response()->streamDownload(function () use ($rows, $total, $fromJalali, $toJalali) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            $safe = static function ($value): string {
                $value = (string) ($value ?? '');
                return preg_match('/^[=+\-@]/u', $value) ? "'{$value}" : $value;
            };

            fputcsv($output, ['گزارش مبالغ دوزوله‌های صادرشده']);
            fputcsv($output, ['از تاریخ شمسی', $fromJalali, 'تا تاریخ شمسی', $toJalali]);
            fputcsv($output, ['تعداد دوزوله صادرشده', $rows->count(), 'جمع مبلغ (ریال)', $total]);
            fputcsv($output, []);
            fputcsv($output, ['ردیف', 'کد رهگیری', 'شماره دوزوله', 'شرکت', 'کشور مقصد', 'مقصد بارگیری', 'مبلغ دوزوله (ریال)', 'تاریخ صدور شمسی', 'شناسه ردیف']);

            foreach ($rows as $index => $row) {
                fputcsv($output, [
                    $index + 1, $safe($row->tracking_code), $safe($row->serial_number),
                    $safe($row->company_name_fa ?: $row->company_name ?: 'نامشخص'),
                    $safe($row->country_name ?: 'نامشخص'), $safe($row->loading_destination ?: '---'),
                    (float) $row->deducted_amount,
                    Jalalian::fromCarbon(Carbon::parse($row->issued_at))->format('Y/m/d H:i'),
                    $row->item_id,
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['', '', '', '', '', 'جمع کل', $total]);
            fclose($output);
        }, 'issued-dozbalagh-' . str_replace('/', '-', $fromJalali) . '-to-' . str_replace('/', '-', $toJalali) . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function requestSettlement(Request $request)
    {
        $period = $this->settlementTargetPeriod();
        abort_if((int) Jalalian::now()->getDay() > 5, 422, 'ثبت درخواست تسویه ماه بسته‌شده فقط از روز ۱ تا ۵ ماه بعد فعال است.');

        return DB::transaction(function () use ($request, $period) {
            $existing = IssuedDozbalaghSettlementRequest::where('period_key', $period['key'])->lockForUpdate()->first();
            if ($existing) {
                return back()->withErrors(['settlement' => 'درخواست تسویه این ماه قبلاً ثبت شده است.']);
            }

            $query = $this->reportQuery($period['from'], $period['to'], null);
            $count = (clone $query)->count();
            $amount = (clone $query)->sum('item.price');
            if ($count === 0 || (float) $amount <= 0) {
                return back()->withErrors(['settlement' => 'برای این ماه دوزوله صادرشده‌ای جهت تسویه وجود ندارد.']);
            }

            IssuedDozbalaghSettlementRequest::create([
                'period_key' => $period['key'], 'period_from' => $period['from'], 'period_to' => $period['to'],
                'issued_count' => $count, 'requested_amount' => $amount, 'status' => 'requested',
                'requested_by_user_id' => $request->user()->id, 'requested_at' => now(),
            ]);

            return back()->with('success', 'درخواست تسویه ماه با مبلغ محاسبه‌شده سامانه ثبت شد.');
        });
    }

    public function confirmSettlement(Request $request, IssuedDozbalaghSettlementRequest $settlement)
    {
        $validated = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:1'],
            'bank_name' => ['required', 'string', 'max:150'],
            'payment_reference' => ['required', 'string', 'max:150'],
            'receipt_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($settlement->status !== 'requested') {
            return back()->withErrors(['settlement' => 'این درخواست قبلاً تعیین تکلیف شده است.']);
        }

        if (abs((float) $validated['paid_amount'] - (float) $settlement->requested_amount) >= 0.01) {
            return back()->withErrors(['paid_amount' => 'مبلغ پرداختی باید دقیقاً برابر مبلغ درخواست تسویه باشد.']);
        }

        $receiptPath = $request->file('receipt_file')->store('settlement-receipts', 'public');
        $settlement->update([
            'status' => 'paid', 'paid_by_user_id' => $request->user()->id,
            'paid_amount' => $validated['paid_amount'], 'bank_name' => $validated['bank_name'],
            'payment_reference' => $validated['payment_reference'], 'receipt_file' => $receiptPath,
            'paid_at' => now(), 'note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', 'پرداخت و سند تسویه با موفقیت ثبت شد.');
    }

    private function filters(Request $request): array
    {
        $period = $this->currentJalaliPeriod();
        foreach (['date_from_jalali', 'date_to_jalali'] as $field) {
            if ($request->filled($field)) {
                $request->merge([$field => $this->toEnglishDigits((string) $request->input($field))]);
            }
        }
        $validated = $request->validate([
            'date_from_jalali' => ['nullable', 'regex:/^1[34-9]\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/'],
            'date_to_jalali' => ['nullable', 'regex:/^1[34-9]\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $fromJalali = $validated['date_from_jalali'] ?? $period['from_jalali'];
        $toJalali = $validated['date_to_jalali'] ?? $period['to_jalali'];

        try {
            $from = Jalalian::fromFormat('Y/m/d', $fromJalali)->toCarbon()->format('Y-m-d');
            $to = Jalalian::fromFormat('Y/m/d', $toJalali)->toCarbon()->format('Y-m-d');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['date_from_jalali' => 'تاریخ شمسی واردشده معتبر نیست.']);
        }

        if (Carbon::parse($from)->gt(Carbon::parse($to)) || Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 31) {
            throw ValidationException::withMessages(['date_to_jalali' => 'بازه شمسی باید حداکثر ۳۱ روز و تاریخ پایان بعد از شروع باشد.']);
        }

        return [$from, $to, $validated['company_id'] ?? null, $fromJalali, $toJalali];
    }

    private function currentJalaliPeriod(): array
    {
        $now = Jalalian::now();
        $year = (int) $now->getYear();
        $month = (int) $now->getMonth();
        $start = (new Jalalian($year, $month, 1))->toCarbon()->startOfDay();
        $nextYear = $month === 12 ? $year + 1 : $year;
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $end = (new Jalalian($nextYear, $nextMonth, 1))->toCarbon()->subDay()->endOfDay();

        return [
            'key' => sprintf('%04d-%02d', $year, $month),
            'label' => sprintf('%04d/%02d', $year, $month),
            'from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'),
            'from_jalali' => sprintf('%04d/%02d/01', $year, $month),
            'to_jalali' => Jalalian::fromCarbon($end)->format('Y/m/d'),
        ];
    }

    private function settlementTargetPeriod(): array
    {
        $current = $this->currentJalaliPeriod();
        $previousEnd = Carbon::parse($current['from'])->subDay();
        $previousJalali = Jalalian::fromCarbon($previousEnd);
        $year = (int) $previousJalali->getYear();
        $month = (int) $previousJalali->getMonth();
        $start = (new Jalalian($year, $month, 1))->toCarbon()->startOfDay();

        return [
            'key' => sprintf('%04d-%02d', $year, $month),
            'label' => sprintf('%04d/%02d', $year, $month),
            'from' => $start->format('Y-m-d'),
            'to' => $previousEnd->format('Y-m-d'),
        ];
    }

    private function toEnglishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private function reportQuery(string $from, string $to, ?int $companyId): Builder
    {
        return DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->join('companies as company', 'company.id', '=', 'permit.company_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->whereNotNull('item.d_serial_number')->whereNotNull('item.issued_at')
            ->whereBetween('item.issued_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->when($companyId, fn (Builder $query) => $query->where('permit.company_id', $companyId))
            ->orderByDesc('item.issued_at')->orderByDesc('item.id')
            ->select(['item.id as item_id', 'permit.d_code as tracking_code', 'item.d_serial_number as serial_number',
                'company.name as company_name', 'company.name_fa as company_name_fa', 'country.name as country_name',
                'item.loading_destination', 'item.price as deducted_amount', 'item.issued_at']);
    }
}

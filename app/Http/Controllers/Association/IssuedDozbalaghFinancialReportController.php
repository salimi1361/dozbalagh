<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssuedDozbalaghFinancialReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $companyId] = $this->filters($request);
        $query = $this->reportQuery($from, $to, $companyId);

        return view('association.issued-financial.index', [
            'rows' => (clone $query)->paginate(25)->withQueryString(),
            'totalAmount' => (clone $query)->sum('item.price'),
            'totalCount' => (clone $query)->count(),
            'companies' => Company::query()->orderBy('name_fa')->get(['id', 'name_fa', 'name']),
            'dateFrom' => $from,
            'dateTo' => $to,
            'companyId' => $companyId,
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to, $companyId] = $this->filters($request);
        $rows = $this->reportQuery($from, $to, $companyId)->get();
        $total = $rows->sum(fn ($row) => (float) $row->deducted_amount);
        $fileName = "issued-dozbalagh-amounts-{$from}-to-{$to}.csv";

        return response()->streamDownload(function () use ($rows, $total, $from, $to) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            $safe = static function ($value): string {
                $value = (string) ($value ?? '');
                return preg_match('/^[=+\-@]/u', $value) ? "'{$value}" : $value;
            };

            fputcsv($output, ['گزارش مبالغ دوزوله‌های صادرشده']);
            fputcsv($output, ['از تاریخ', $from, 'تا تاریخ', $to]);
            fputcsv($output, ['تعداد دوزوله صادرشده', $rows->count(), 'جمع مبلغ (ریال)', $total]);
            fputcsv($output, []);
            fputcsv($output, ['ردیف', 'کد رهگیری', 'شماره دوزوله', 'شرکت', 'کشور مقصد', 'مقصد بارگیری', 'مبلغ دوزوله (ریال)', 'تاریخ صدور شمسی', 'شناسه ردیف']);

            foreach ($rows as $index => $row) {
                fputcsv($output, [
                    $index + 1,
                    $safe($row->tracking_code),
                    $safe($row->serial_number),
                    $safe($row->company_name_fa ?: $row->company_name ?: 'نامشخص'),
                    $safe($row->country_name ?: 'نامشخص'),
                    $safe($row->loading_destination ?: '---'),
                    (float) $row->deducted_amount,
                    \Morilog\Jalali\Jalalian::fromCarbon(Carbon::parse($row->issued_at))->format('Y/m/d H:i'),
                    $row->item_id,
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['', '', '', '', '', 'جمع کل', $total]);
            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $to = $validated['date_to'] ?? now()->endOfMonth()->format('Y-m-d');

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 31) {
            throw ValidationException::withMessages(['date_to' => 'بازه گزارش نمی‌تواند بیشتر از ۳۱ روز باشد.']);
        }

        return [$from, $to, $validated['company_id'] ?? null];
    }

    private function reportQuery(string $from, string $to, ?int $companyId): Builder
    {
        return DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->join('companies as company', 'company.id', '=', 'permit.company_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->whereNotNull('item.d_serial_number')
            ->whereNotNull('item.issued_at')
            ->whereBetween('item.issued_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->when($companyId, fn (Builder $query) => $query->where('permit.company_id', $companyId))
            ->orderByDesc('item.issued_at')
            ->orderByDesc('item.id')
            ->select([
                'item.id as item_id',
                'permit.d_code as tracking_code',
                'item.d_serial_number as serial_number',
                'company.name as company_name',
                'company.name_fa as company_name_fa',
                'country.name as country_name',
                'item.loading_destination',
                'item.price as deducted_amount',
                'item.issued_at',
            ]);
    }
}

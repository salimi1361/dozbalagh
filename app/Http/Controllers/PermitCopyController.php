<?php

namespace App\Http\Controllers;

use App\Services\PermitPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class PermitCopyController extends Controller
{
    public function companyIndex(Request $request)
    {
        $companyId = $request->user()?->company?->id;
        abort_unless($companyId, 403);
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $items = $this->companyIssuedQuery($request, $companyId)
            ->when($dateFrom, fn ($query) => $query->whereDate('pri.issued_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($query) => $query->whereDate('pri.issued_at', '<=', $dateTo->toDateString()))
            ->orderByDesc('pri.issued_at')->orderByDesc('pri.id')->paginate(20)->withQueryString();

        $issuedStatsQuery = DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->where('pr.company_id', $companyId)
            ->whereNotNull('pri.d_serial_number')
            ->where('pri.d_serial_number', '<>', '');
        $issuedStats = [
            'total' => (clone $issuedStatsQuery)->count(),
            'valid' => (clone $issuedStatsQuery)
                ->whereNotNull('pri.permit_valid_until')
                ->whereDate('pri.permit_valid_until', '>=', today()->toDateString())
                ->count(),
            'countries' => (clone $issuedStatsQuery)
                ->whereNotNull('pri.country_id')
                ->distinct()
                ->count('pri.country_id'),
        ];

        return view('company.dozbalagh.issued', [
            'items' => $items,
            'dateFromJalali' => $request->input('date_from_jalali'),
            'dateToJalali' => $request->input('date_to_jalali'),
            'issuedStats' => $issuedStats,
        ]);
    }

    public function companyExport(Request $request)
    {
        $companyId = $request->user()?->company?->id;
        abort_unless($companyId, 403);
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $items = $this->companyIssuedQuery($request, $companyId)
            ->when($dateFrom, fn ($query) => $query->whereDate('pri.issued_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($query) => $query->whereDate('pri.issued_at', '<=', $dateTo->toDateString()))
            ->orderByDesc('pri.issued_at')->orderByDesc('pri.id')->get();
        $permitTypes = $this->permitTypeLabels();

        return response()->streamDownload(function () use ($items, $permitTypes) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'سریال دوزوله', 'کد رهگیری', 'کشور', 'نوع مجوز', 'راننده',
                'شماره ترانزیت اسب (کشنده)', 'شماره ترانزیت یدک (بارگیر)',
                'مبدأ بارگیری', 'مقصد بارگیری', 'تاریخ صدور', 'پایان اعتبار',
            ]);
            foreach ($items as $item) {
                fputcsv($output, [
                    $item->d_serial_number,
                    $item->d_code,
                    $item->country_name,
                    $permitTypes[$item->permit_type] ?? $item->permit_type,
                    trim(($item->first_name_fa ?? '').' '.($item->last_name_fa ?? '')),
                    $item->transit_horse,
                    $item->transit_trailer,
                    $item->loading_origin,
                    $item->loading_destination,
                    $item->issued_at ? verta($item->issued_at)->format('Y/m/d') : '',
                    $item->permit_valid_until ? verta($item->permit_valid_until)->format('Y/m/d') : '',
                ]);
            }
            fclose($output);
        }, 'dozoleh-issued-'.now()->format('Y-m-d-H-i').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function companyIssuedQuery(Request $request, int $companyId)
    {
        return DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->leftJoin('countries as c', 'c.id', '=', 'pri.country_id')
            ->leftJoin('drivers as d', 'd.id', '=', 'pr.driver_id')
            ->leftJoin('fleets as f', 'f.id', '=', 'pr.fleet_id')
            ->where('pr.company_id', $companyId)->whereNotNull('pri.d_serial_number')->where('pri.d_serial_number', '<>', '')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.trim((string) $request->input('search')).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('pri.d_serial_number', 'like', $term)
                        ->orWhere('pr.d_code', 'like', $term)
                        ->orWhere('c.name', 'like', $term)
                        ->orWhere('d.first_name_fa', 'like', $term)
                        ->orWhere('d.last_name_fa', 'like', $term)
                        ->orWhere('f.transit_horse', 'like', $term)
                        ->orWhere('f.transit_trailer', 'like', $term);
                });
            })
            ->select([
                'pri.id', 'pri.d_serial_number', 'pri.permit_type', 'pri.operation_type',
                'pri.loading_origin', 'pri.loading_destination', 'pri.issued_at', 'pri.permit_valid_until',
                'pr.d_code', 'pr.status', 'c.name as country_name', 'd.first_name_fa', 'd.last_name_fa',
                'f.transit_horse', 'f.transit_trailer',
            ]);
    }

    private function permitTypeLabels(): array
    {
        return [
            'bilateral' => 'دوجانبه',
            'transit' => 'ترانزیتی',
            'bilateral_transit' => 'دوجانبه ترانزیتی',
            'third_country' => 'کشور ثالث',
            'third_country_transit' => 'ترانزیت کشور ثالث',
        ];
    }

    private function dateRange(Request $request): array
    {
        $from = $this->parseJalaliDate($request->input('date_from_jalali'), 'date_from_jalali');
        $to = $this->parseJalaliDate($request->input('date_to_jalali'), 'date_to_jalali');

        if ($from && $to && $from->gt($to)) {
            throw ValidationException::withMessages([
                'date_to_jalali' => 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.',
            ]);
        }

        return [$from, $to];
    }

    private function parseJalaliDate(mixed $value, string $field): ?\Carbon\Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            $normalized = strtr(trim((string) $value), [
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            ]);

            return Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon()->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'تاریخ شمسی انتخاب‌شده معتبر نیست.',
            ]);
        }
    }

    public function company(Request $request, int $item, PermitPrintService $printService)
    {
        $companyId = $request->user()?->company?->id;
        abort_unless($companyId, 403);
        $owned = DB::table('permit_request_items as pri')->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->where('pri.id', $item)->where('pr.company_id', $companyId)->whereNotNull('pri.d_serial_number')->exists();
        abort_unless($owned, 404);
        return $this->renderCopy($printService, $item, 'نسخه شرکت');
    }

    public function driver(Request $request, int $item, PermitPrintService $printService)
    {
        $driver = $request->user();
        $owned = DB::table('permit_request_items as pri')->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->where('pri.id', $item)->where('pr.driver_id', $driver->id)->whereNotNull('pri.d_serial_number')->exists();
        abort_unless($owned, 404);
        return $this->renderCopy($printService, $item, 'نسخه راننده');
    }

    private function renderCopy(PermitPrintService $printService, int $item, string $copyTitle)
    {
        $context = $printService->contextForItem($item);
        abort_unless($context['layout'] && $context['layout']->background_path, 404, 'قالب تصویری این دوزوله هنوز آماده نشده است.');
        return response()->view('association.driver.dynamic_print', $context + ['mode' => 'copy', 'copyOnly' => true, 'copyTitle' => $copyTitle])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }
}

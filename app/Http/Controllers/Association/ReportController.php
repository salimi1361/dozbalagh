<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $permits = $this->buildQuery($request)->orderByDesc('pri.id')->paginate(25)->withQueryString();
        $statusExpression = $this->statusExpression();
        $statusCounts = DB::table('permit_request_items')
            ->whereNotNull('d_serial_number')
            ->selectRaw("{$statusExpression} as report_status, COUNT(*) as total")
            ->groupByRaw($statusExpression)
            ->pluck('total', 'report_status');

        return view('association.report.index', [
            'permits' => $permits,
            'statusCounts' => $statusCounts,
            'statusOptions' => $this->statusOptions(),
            'companies' => Company::query()->orderBy('name_fa')->get(['id', 'name_fa', 'name']),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $permits = $this->buildQuery($request)->orderByDesc('pri.id')->get();
        $labels = $this->statusOptions();

        return response()->streamDownload(function () use ($permits, $labels) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ردیف', 'کد رهگیری', 'سریال دوزوله', 'شرکت', 'راننده', 'کد ملی راننده', 'کارت هوشمند ناوگان', 'پلاک', 'کشور', 'نوع درخواست', 'نوع مجوز', 'وضعیت دوزوله', 'تاریخ ثبت', 'تاریخ صدور', 'تاریخ اعتبار']);

            foreach ($permits as $index => $permit) {
                fputcsv($output, [
                    $index + 1, $permit->d_code, $permit->serial_number, $permit->company_name,
                    trim(($permit->driver_first_name ?? '') . ' ' . ($permit->driver_last_name ?? '')), $permit->driver_national_code, $permit->fleet_smart_card,
                    $permit->fleet_plate, $permit->country_name,
                    $permit->request_type === 'renewal' ? 'تمدید' : 'جدید', $permit->permit_type,
                    $labels[$permit->report_status] ?? $permit->report_status,
                    $this->jalaliDate($permit->created_at, true), $this->jalaliDate($permit->issued_at, true),
                    $this->jalaliDate($permit->permit_valid_until),
                ]);
            }
            fclose($output);
        }, 'association-issued-dozoule-' . now()->format('Y-m-d-H-i') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery(Request $request): Builder
    {
        $issuedAtColumn = Schema::hasColumn('permit_request_items', 'issued_at')
            ? 'pri.issued_at'
            : DB::raw('NULL as issued_at');
        $validUntilColumn = Schema::hasColumn('permit_request_items', 'permit_valid_until')
            ? 'pri.permit_valid_until'
            : DB::raw('NULL as permit_valid_until');

        $query = DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->leftJoin('companies as co', 'co.id', '=', 'pr.company_id')
            ->leftJoin('drivers as d', 'd.id', '=', 'pr.driver_id')
            ->leftJoin('fleets as f', 'f.id', '=', 'pr.fleet_id')
            ->leftJoin('countries as c', 'c.id', '=', 'pri.country_id')
            ->whereNotNull('pri.d_serial_number')
            ->select([
                'pri.id', 'pr.d_code', 'pri.d_serial_number as serial_number',
                DB::raw('COALESCE(co.name_fa, co.name) as company_name'),
                'd.first_name_fa as driver_first_name', 'd.last_name_fa as driver_last_name',
                'd.national_code as driver_national_code', 'f.smart_card_number as fleet_smart_card',
                'f.transit_plate as fleet_plate', 'c.name as country_name', 'pr.request_type',
                'pri.permit_type', DB::raw($this->statusExpression('pri') . ' as report_status'),
                'pr.created_at', $issuedAtColumn, $validUntilColumn,
            ]);

        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->input('search')) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('pr.d_code', 'like', $search)->orWhere('pri.d_serial_number', 'like', $search)
                    ->orWhere('co.name_fa', 'like', $search)->orWhere('co.name', 'like', $search)
                    ->orWhere('d.first_name_fa', 'like', $search)->orWhere('d.last_name_fa', 'like', $search)
                    ->orWhere('d.national_code', 'like', $search)->orWhere('f.smart_card_number', 'like', $search)
                    ->orWhere('f.transit_plate', 'like', $search);
            });
        }

        $query->when($request->filled('status'), fn (Builder $q) => $q->whereRaw($this->statusExpression('pri') . ' = ?', [$request->input('status')]))
            ->when($request->filled('request_type'), fn (Builder $q) => $q->where('pr.request_type', $request->input('request_type')))
            ->when($request->filled('company_id'), fn (Builder $q) => $q->where('pr.company_id', $request->integer('company_id')))
            ->when($request->filled('country_id'), fn (Builder $q) => $q->where('pri.country_id', $request->integer('country_id')))
            ->when($request->filled('date_from'), function (Builder $q) use ($request) {
                $column = Schema::hasColumn('permit_request_items', 'issued_at') ? 'pri.issued_at' : 'pr.created_at';
                $q->whereDate($column, '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function (Builder $q) use ($request) {
                $column = Schema::hasColumn('permit_request_items', 'issued_at') ? 'pri.issued_at' : 'pr.created_at';
                $q->whereDate($column, '<=', $request->input('date_to'));
            });

        return $query;
    }

    private function jalaliDate($date, bool $withTime = false): string
    {
        if (!$date) return '';
        return \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($date))->format($withTime ? 'Y/m/d H:i' : 'Y/m/d');
    }

    private function statusOptions(): array
    {
        return ['issued' => 'صادرشده / فعال', 'company_returned' => 'لاشه در مسیر انجمن', 'collected' => 'لاشه تحویل‌شده', 'archived' => 'بایگانی‌شده', 'lost' => 'مفقودی', 'extended' => 'تمدیدشده', 'cancelled' => 'باطل‌شده'];
    }

    private function statusExpression(string $alias = ''): string
    {
        $prefix = $alias === '' ? '' : $alias . '.';
        $statusColumn = Schema::hasColumn('permit_request_items', 'item_status')
            ? $prefix . 'item_status'
            : (Schema::hasColumn('permit_request_items', 'return_status') ? $prefix . 'return_status' : "'issued'");

        $returnCondition = Schema::hasColumn('permit_request_items', 'company_return_submitted_at')
            ? "{$prefix}company_return_submitted_at IS NOT NULL"
            : (Schema::hasColumn('permit_request_items', 'return_status') ? "{$prefix}return_status = 'company_returned'" : '1 = 0');

        return "CASE WHEN {$statusColumn} IN ('lost', 'collected', 'archived', 'extended', 'cancelled') THEN {$statusColumn} WHEN {$returnCondition} THEN 'company_returned' ELSE 'issued' END";
    }
}

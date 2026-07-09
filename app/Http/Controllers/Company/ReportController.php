<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\PermitRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $baseQuery = DB::table('permit_requests')->where('company_id', $companyId);

        $counts = [
            'issued'    => (clone $baseQuery)->where('status', 'issued')->count(),
            'pending'   => (clone $baseQuery)->whereIn('status', ['pending', 'approved'])->count(),
            'collected' => (clone $baseQuery)->whereIn('status', ['collected', 'archived'])->count(),
            'returned'  => (clone $baseQuery)->where('status', 'returned')->count(),
            'renewed'   => (clone $baseQuery)->where('request_type', 'renewal')->count(),
            'lost'      => (clone $baseQuery)->where('status', 'lost')->count(),
        ];

        $statusOptions = $this->statusOptions();
        $permits = $this->buildPermitQuery($request, $companyId)
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('company.report.index', compact('counts', 'permits', 'statusOptions'));
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $permits = $this->buildPermitQuery($request, $companyId)
            ->orderBy('id', 'desc')
            ->get();

        $fileName = 'company_permits_report_' . now()->format('Y-m-d_H-i') . '.csv';
        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'کد رهگیری',
            'راننده',
            'کد ملی راننده',
            'ناوگان',
            'پلاک',
            'سریال',
            'نوع درخواست',
            'وضعیت',
            'مبلغ کل',
            'تاریخ ثبت شمسی',
            'تاریخ ثبت میلادی',
        ];

        $statusLabels = $this->statusLabels();

        $callback = function () use ($permits, $columns, $statusLabels) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            foreach ($permits as $permit) {
                $driverName = trim(($permit->driver->first_name_fa ?? '') . ' ' . ($permit->driver->last_name_fa ?? ''));
                $requestType = ($permit->request_type ?? 'new') === 'renewal' ? 'تمدیدی' : 'جدید';
                $createdAt = $permit->created_at ? \Carbon\Carbon::parse($permit->created_at) : null;

                fputcsv($file, [
                    $permit->d_code,
                    $driverName ?: 'نامشخص',
                    $permit->driver->national_code ?? '',
                    $permit->fleet->smart_card_number ?? $permit->fleet_id,
                    $permit->fleet->transit_plate ?? '',
                    $permit->serial_number,
                    $requestType,
                    $statusLabels[$permit->status] ?? $permit->status,
                    $permit->total_amount,
                    $createdAt ? \Morilog\Jalali\Jalalian::fromCarbon($createdAt)->format('Y/m/d H:i') : '',
                    $createdAt ? $createdAt->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildPermitQuery(Request $request, $companyId): Builder
    {
        $query = PermitRequest::query()
            ->with(['driver', 'fleet'])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function (Builder $q) use ($search) {
                $q->where('d_code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $q->orWhere('driver_id', $search)
                        ->orWhere('fleet_id', $search);
                }

                $q->orWhereHas('driver', function (Builder $driver) use ($search) {
                        $driver->where('first_name_fa', 'like', "%{$search}%")
                            ->orWhere('last_name_fa', 'like', "%{$search}%")
                            ->orWhere('national_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fleet', function (Builder $fleet) use ($search) {
                        $fleet->where('transit_plate', 'like', "%{$search}%")
                            ->orWhere('smart_card_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;

            if (in_array($status, ['renewal', 'renewed'], true)) {
                $query->where('request_type', 'renewal');
            } elseif ($status === 'pending') {
                $query->whereIn('status', ['pending', 'approved', 'under_review']);
            } elseif ($status === 'collected') {
                $query->whereIn('status', ['collected', 'archived']);
            } else {
                $query->where('status', $status);
            }
        }

        return $query;
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'پیش نویس',
            'pending' => 'در حال بررسی',
            'approved' => 'تایید شده',
            'issued' => 'صادر شده',
            'rejected' => 'رد شده',
            'collected' => 'لاشه تحویل شده',
            'archived' => 'بایگانی شده',
            'returned' => 'نیاز به اصلاح',
            'renewal' => 'تمدیدی',
            'lost' => 'مفقودی',
        ];
    }

    private function statusLabels(): array
    {
        return [
            'draft' => 'پیش نویس',
            'pending' => 'در حال بررسی',
            'under_review' => 'در حال بررسی',
            'approved' => 'تایید شده',
            'issued' => 'صادر شده',
            'rejected' => 'رد شده',
            'returned' => 'نیاز به اصلاح',
            'collected' => 'لاشه تحویل شده',
            'archived' => 'بایگانی شده',
            'lost' => 'مفقودی',
        ];
    }
}

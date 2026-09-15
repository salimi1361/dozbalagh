<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->buildQuery($filters);
        $events = (clone $query)->orderByDesc('event.created_at')->paginate(30)->withQueryString();
        $counts = $this->summary((clone $query));

        return view('association.performance-report.index', [
            'events' => $events,
            'counts' => $counts,
            'filters' => $filters,
            'eventLabels' => $this->eventLabels(),
            'statusLabels' => $this->statusLabels(),
            'eventTypes' => DB::table('permit_item_events')
                ->where('actor_role', 'association')->whereNotNull('actor_user_id')
                ->distinct()->orderBy('event_type')->pluck('event_type'),
            'associationUsers' => DB::table('users as user')
                ->join('roles as role', 'role.id', '=', 'user.role_id')
                ->where('role.name', 'association')->orderBy('user.username')
                ->get(['user.id', 'user.username']),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $events = $this->buildQuery($filters)->orderBy('event.created_at')->get();
        $labels = $this->eventLabels();
        $statusLabels = $this->statusLabels();
        $filename = 'association-performance-'.$filters['date_from'].'-to-'.$filters['date_to'].'.csv';

        return response()->streamDownload(function () use ($events, $labels, $statusLabels) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ردیف', 'تاریخ و ساعت شمسی', 'کاربر انجمن', 'نوع اقدام', 'کد رهگیری', 'شماره دوزوله', 'شرکت', 'کشور', 'وضعیت قبل', 'وضعیت بعد', 'شرح اقدام', 'IP']);
            foreach ($events as $index => $event) {
                fputcsv($output, [
                    $index + 1,
                    verta($event->created_at)->format('Y/m/d H:i:s'),
                    $event->actor_name,
                    $labels[$event->event_type] ?? $event->event_type,
                    $event->d_code,
                    $event->serial_number,
                    $event->company_name,
                    $event->country_name,
                    $statusLabels[$event->from_status] ?? $event->from_status,
                    $statusLabels[$event->to_status] ?? $event->to_status,
                    $event->description,
                    $event->ip_address,
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'date_from' => $request->input('date_from', now()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'date_from_view' => $request->input('date_from_view', verta()->format('Y/m/d')),
            'date_to_view' => $request->input('date_to_view', verta()->format('Y/m/d')),
            'user_id' => $request->integer('user_id') ?: null,
            'event_type' => $request->input('event_type'),
            'search' => trim((string) $request->input('search')),
        ];
    }

    private function buildQuery(array $filters): Builder
    {
        $query = DB::table('permit_item_events as event')
            ->join('users as actor', 'actor.id', '=', 'event.actor_user_id')
            ->join('permit_requests as request', 'request.id', '=', 'event.permit_request_id')
            ->join('permit_request_items as item', 'item.id', '=', 'event.permit_request_item_id')
            ->leftJoin('companies as company', 'company.id', '=', 'request.company_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->where('event.actor_role', 'association')
            ->whereNotNull('event.actor_user_id')
            ->whereDate('event.created_at', '>=', $filters['date_from'])
            ->whereDate('event.created_at', '<=', $filters['date_to'])
            ->when($filters['user_id'], fn (Builder $q, int $id) => $q->where('event.actor_user_id', $id))
            ->when($filters['event_type'], fn (Builder $q, string $type) => $q->where('event.event_type', $type))
            ->when($filters['search'], function (Builder $q, string $search) {
                $term = '%'.$search.'%';
                $q->where(function (Builder $q) use ($term) {
                    $q->where('request.d_code', 'like', $term)
                        ->orWhere('item.d_serial_number', 'like', $term)
                        ->orWhere('company.name_fa', 'like', $term)
                        ->orWhere('country.name', 'like', $term)
                        ->orWhere('event.description', 'like', $term);
                });
            });

        return $query->select([
            'event.id', 'event.permit_request_item_id', 'event.actor_user_id', 'actor.username as actor_name', 'event.event_type',
            'event.from_status', 'event.to_status', 'event.description', 'event.ip_address', 'event.created_at',
            'request.d_code', 'item.d_serial_number as serial_number',
            DB::raw('COALESCE(company.name_fa, company.name) as company_name'), 'country.name as country_name',
        ]);
    }

    private function summary(Builder $query): array
    {
        $rows = $query->reorder()->select('event.event_type', DB::raw('COUNT(*) as total'))
            ->groupBy('event.event_type')->pluck('total', 'event.event_type');

        return [
            'total' => (int) $rows->sum(),
            'approved' => (int) ($rows['item_approved'] ?? 0),
            'rejected' => (int) ($rows['item_rejected'] ?? 0),
            'issued' => (int) ($rows['permit_issued'] ?? 0),
            'other' => (int) ($rows->sum() - ($rows['item_approved'] ?? 0) - ($rows['item_rejected'] ?? 0) - ($rows['permit_issued'] ?? 0)),
        ];
    }

    private function eventLabels(): array
    {
        return [
            'item_approved' => 'تأیید درخواست',
            'item_rejected' => 'رد درخواست',
            'permit_issued' => 'صدور و تخصیص سریال',
            'permit_serial_corrected' => 'اصلاح شماره دوزوله',
            'association_edit' => 'ویرایش اطلاعات توسط انجمن',
            'cits_result' => 'ثبت نتیجه CITS',
            'disposition_changed' => 'تعیین تکلیف دوزوله',
            'lash_received' => 'تحویل لاشه مصرف‌شده',
            'unused_return_received' => 'تحویل دوزوله استفاده‌نشده',
            'outbound_courier_assigned' => 'معرفی پیک تحویل',
            'outbound_courier_completed' => 'تحویل دوزوله به پیک',
            'unused_permit_transferred' => 'انتقال دوزوله برگشتی',
            'renewal_completed' => 'تکمیل تمدید',
            'association_status_seen' => 'مشاهده اعلام وضعیت شرکت',
        ];
    }

    private function statusLabels(): array
    {
        return [
            'pending' => 'در انتظار بررسی', 'under_review' => 'در حال بررسی',
            'approved' => 'تأییدشده', 'ready_to_issue' => 'آماده صدور',
            'rejected' => 'ردشده', 'issued' => 'صادرشده',
            'company_returned' => 'ارسال لاشه توسط شرکت', 'cits_processing' => 'در حال پردازش CITS',
            'returned_unused' => 'برگشتی استفاده‌نشده', 'collected' => 'لاشه دریافت‌شده',
            'archived' => 'بایگانی‌شده', 'lost_reported' => 'اعلام مفقودی',
            'lost' => 'مفقودی قطعی', 'extended' => 'تمدیدشده',
            'cancelled' => 'باطل‌شده', 'transferred_in' => 'منتقل‌شده',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\PermitPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermitCopyController extends Controller
{
    public function companyIndex(Request $request)
    {
        $companyId = $request->user()?->company?->id;
        abort_unless($companyId, 403);
        $items = DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->leftJoin('countries as c', 'c.id', '=', 'pri.country_id')
            ->leftJoin('drivers as d', 'd.id', '=', 'pr.driver_id')
            ->leftJoin('fleets as f', 'f.id', '=', 'pr.fleet_id')
            ->where('pr.company_id', $companyId)->whereNotNull('pri.d_serial_number')->where('pri.d_serial_number', '<>', '')
            ->select(['pri.id', 'pri.d_serial_number', 'pri.permit_type', 'pri.operation_type', 'pri.loading_origin', 'pri.loading_destination', 'pri.issued_at', 'pri.permit_valid_until', 'pr.d_code', 'pr.status', 'c.name as country_name', 'd.first_name_fa', 'd.last_name_fa', 'f.transit_plate'])
            ->orderByDesc('pri.issued_at')->orderByDesc('pri.id')->paginate(20);
        return view('company.dozbalagh.issued', compact('items'));
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

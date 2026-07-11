<?php

namespace App\Http\Controllers;

use App\Services\PermitPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermitCopyController extends Controller
{
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

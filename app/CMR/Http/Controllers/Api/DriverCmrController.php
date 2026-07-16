<?php

namespace App\CMR\Http\Controllers\Api;

use App\CMR\Models\CmrDocument;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DriverCmrController extends Controller
{
    public function index(): JsonResponse
    {
        $driverId = (int) auth()->id();
        $documents = CmrDocument::query()
            ->where('driver_id', $driverId)
            ->whereIn('status', ['issued', 'accepted', 'in_transit', 'delivered', 'finalized', 'cancelled'])
            ->with(['company:id,name_fa,name_en', 'fleet:id,transit_plate,smart_card_number,truck_type'])
            ->latest('issued_at')
            ->get()
            ->map(fn (CmrDocument $document) => $this->summary($document));

        return response()->json(['data' => $documents]);
    }

    public function show(CmrDocument $cmr): JsonResponse
    {
        abort_unless((int) $cmr->driver_id === (int) auth()->id(), 403);
        abort_if($cmr->status === 'draft', 404);
        $cmr->load(['company:id,name_fa,name_en,address_en', 'fleet', 'goods']);

        return response()->json(['data' => $this->summary($cmr) + [
            'consignor' => ['name' => $cmr->consignor_name, 'address' => $cmr->consignor_address, 'country_code' => $cmr->consignor_country_code],
            'consignee' => ['name' => $cmr->consignee_name, 'address' => $cmr->consignee_address, 'country_code' => $cmr->consignee_country_code],
            'carrier' => ['name' => $cmr->carrier_name, 'address' => $cmr->carrier_address, 'country_code' => $cmr->carrier_country_code],
            'goods' => $cmr->goods->map(fn ($good) => [
                'line_number' => $good->line_number,
                'description' => $good->description,
                'marks_and_numbers' => $good->marks_and_numbers,
                'package_type' => $good->package_type,
                'package_count' => $good->package_count,
                'gross_weight_kg' => $good->gross_weight_kg,
                'volume_m3' => $good->volume_m3,
                'un_number' => $good->un_number,
                'adr_class' => $good->adr_class,
            ])->values(),
            'integrity_hash' => $cmr->integrity_hash,
            'version' => $cmr->version,
        ]]);
    }

    public function print(CmrDocument $cmr): View
    {
        abort_unless((int) $cmr->driver_id === (int) auth()->id(), 403);
        abort_if($cmr->status === 'draft', 404);
        $cmr->load(['company', 'driver', 'fleet', 'goods']);

        return view('CMR.print.standard', compact('cmr'));
    }

    private function summary(CmrDocument $document): array
    {
        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'number' => $document->number,
            'company_serial' => $document->company_serial,
            'status' => $document->status,
            'company_name' => $document->company?->name_en ?: $document->company?->name_fa,
            'vehicle_plate' => $document->fleet?->transit_plate,
            'taking_over_place' => $document->taking_over_place,
            'taking_over_at' => $document->taking_over_at?->toIso8601String(),
            'delivery_place' => $document->delivery_place,
            'planned_delivery_at' => $document->planned_delivery_at?->toIso8601String(),
            'issued_at' => $document->issued_at?->toIso8601String(),
            'print_url' => route('api.driver.cmr.print', $document),
        ];
    }
}

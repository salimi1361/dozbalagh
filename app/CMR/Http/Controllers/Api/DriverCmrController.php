<?php

namespace App\CMR\Http\Controllers\Api;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrSignature;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function accept(Request $request, CmrDocument $cmr): JsonResponse
    {
        $data = $request->validate(['reservation' => ['nullable', 'string', 'max:2000'], 'confirmed' => ['accepted']]);
        $this->assertOwner($cmr);
        $driver = auth()->user();
        $name = trim(($driver->first_name_en ?? '').' '.($driver->last_name_en ?? '')) ?: 'Driver #'.$driver->id;

        DB::transaction(function () use ($cmr, $data, $request, $name, $driver) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            abort_unless($document->status === 'issued', 409, 'Only an issued CMR can be accepted.');
            $this->recordSignature($document, 'driver', $name, $data['reservation'] ?? null, $request, (int) $driver->id);
            $document->update(['status' => 'accepted', 'carrier_reservations' => $data['reservation'] ?? $document->carrier_reservations]);
            $this->event($document, 'accepted', 'issued', 'accepted', 'Driver accepted the goods and the current CMR version.', (int) $driver->id);
        });
        return response()->json(['message' => 'CMR accepted.', 'status' => 'accepted']);
    }

    public function start(CmrDocument $cmr): JsonResponse
    {
        $this->assertOwner($cmr);
        DB::transaction(function () use ($cmr) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            abort_unless($document->status === 'accepted', 409, 'The CMR must first be accepted.');
            $document->update(['status' => 'in_transit']);
            $this->event($document, 'transport_started', 'accepted', 'in_transit', 'International road carriage started.', (int) auth()->id());
        });
        return response()->json(['message' => 'Transport started.', 'status' => 'in_transit']);
    }

    public function deliver(Request $request, CmrDocument $cmr): JsonResponse
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate(['consignee_signer_name' => ['required', 'string', 'max:255', $latin], 'reservation' => ['nullable', 'string', 'max:2000'], 'confirmed_by_consignee' => ['accepted']]);
        $this->assertOwner($cmr);
        DB::transaction(function () use ($cmr, $data, $request) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            abort_unless($document->status === 'in_transit', 409, 'Only a CMR in transit can be delivered.');
            $this->recordSignature($document, 'consignee', $data['consignee_signer_name'], $data['reservation'] ?? null, $request, (int) auth()->id(), 'witnessed_on_driver_device');
            $document->update(['status' => 'delivered']);
            $this->event($document, 'delivered', 'in_transit', 'delivered', 'Goods received and box 24 acknowledgement recorded.', (int) auth()->id());
        });
        return response()->json(['message' => 'Delivery recorded.', 'status' => 'delivered']);
    }

    private function assertOwner(CmrDocument $cmr): void
    {
        abort_unless((int) $cmr->driver_id === (int) auth()->id(), 403);
    }

    private function recordSignature(CmrDocument $document, string $role, string $name, ?string $reservation, Request $request, int $driverId, string $type = 'electronic_acknowledgement'): void
    {
        $signedAt = now();
        $evidence = ['cmr_id' => $document->id, 'version' => $document->version, 'role' => $role, 'name' => $name, 'hash' => $document->integrity_hash, 'signed_at' => $signedAt->toIso8601String(), 'ip' => $request->ip()];
        CmrSignature::updateOrCreate(['cmr_document_id' => $document->id, 'document_version' => $document->version, 'signer_role' => $role], ['signer_name' => $name, 'signature_type' => $type, 'reservation' => $reservation, 'document_hash' => $document->integrity_hash, 'evidence_hash' => hash('sha256', json_encode($evidence, JSON_UNESCAPED_UNICODE)), 'driver_id' => $driverId, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000), 'signed_at' => $signedAt]);
    }

    private function event(CmrDocument $document, string $type, string $from, string $to, string $description, int $driverId): void
    {
        CmrEvent::create(['cmr_document_id' => $document->id, 'event_type' => $type, 'from_status' => $from, 'to_status' => $to, 'actor_driver_id' => $driverId, 'actor_role' => 'driver', 'description' => $description, 'occurred_at' => now()]);
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

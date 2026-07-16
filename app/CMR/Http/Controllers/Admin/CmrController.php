<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrPrintTemplate;
use App\CMR\Models\CmrSetting;
use App\CMR\Models\CmrTariffHistory;
use App\CMR\Services\CmrIssuanceService;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CmrController extends Controller
{
    public function index()
    {
        $documents = CmrDocument::with('company')->latest()->paginate(20);
        return view('CMR.admin.index', compact('documents'));
    }

    public function create()
    {
        return view('CMR.admin.create', [
            'companies' => Company::orderBy('name')->get(),
            'drivers' => Driver::orderBy('last_name_fa')->get(),
            'fleets' => Fleet::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'fleet_id' => ['nullable', 'exists:fleets,id'],
            'language' => ['required', 'in:en,fa,de,fr,tr'],
            'transport_type' => ['nullable', 'string', 'max:100'],
            'company_serial' => ['nullable', 'string', 'max:255'],
            'consignor_name' => ['required', 'string', 'max:255', $latin],
            'consignor_identifier' => ['nullable', 'string', 'max:255'],
            'consignor_address' => ['nullable', 'string', $latin],
            'consignor_country_code' => ['nullable', 'string', 'size:2'],
            'consignee_name' => ['required', 'string', 'max:255', $latin],
            'consignee_identifier' => ['nullable', 'string', 'max:255'],
            'consignee_address' => ['nullable', 'string', $latin],
            'consignee_country_code' => ['nullable', 'string', 'size:2'],
            'carrier_name' => ['required', 'string', 'max:255', $latin],
            'carrier_identifier' => ['nullable', 'string', 'max:255'],
            'carrier_address' => ['nullable', 'string', $latin],
            'carrier_country_code' => ['nullable', 'string', 'size:2'],
            'taking_over_place' => ['required', 'string', 'max:255', $latin],
            'taking_over_at' => ['nullable', 'date'],
            'delivery_place' => ['required', 'string', 'max:255', $latin],
            'planned_delivery_at' => ['nullable', 'date'],
            'sender_instructions' => ['nullable', 'string', $latin],
            'special_agreements' => ['nullable', 'string', $latin],
            'attached_documents_text' => ['nullable', 'string', $latin],
            'carrier_reservations' => ['nullable', 'string', $latin],
            'carriage_payment' => ['nullable', 'in:paid,carriage_forward'],
            'cash_on_delivery' => ['nullable', 'numeric', 'min:0'],
            'established_at_place' => ['nullable', 'string', 'max:255', $latin],
            'established_at_date' => ['nullable', 'date'],
            'charges.freight' => ['nullable', 'numeric', 'min:0'],
            'charges.supplementary' => ['nullable', 'numeric', 'min:0'],
            'charges.customs' => ['nullable', 'numeric', 'min:0'],
            'charges.other' => ['nullable', 'numeric', 'min:0'],
            'goods' => ['required', 'array', 'min:1'],
            'goods.*.description' => ['required', 'string', 'max:255', $latin],
            'goods.*.marks_and_numbers' => ['nullable', 'string', 'max:255', $latin],
            'goods.*.package_type' => ['nullable', 'string', 'max:100', $latin],
            'goods.*.package_count' => ['nullable', 'numeric', 'min:0'],
            'goods.*.gross_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'goods.*.volume_m3' => ['nullable', 'numeric', 'min:0'],
            'goods.*.commodity_code' => ['nullable', 'string', 'max:100', $latin],
            'goods.*.un_number' => ['nullable', 'string', 'max:20', $latin],
            'goods.*.adr_class' => ['nullable', 'string', 'max:50', $latin],
        ]);

        $companyId = (int) $data['company_id'];
        if (CmrCompanySetting::forCompany($companyId)->assignment_policy === 'same_company') {
            if (! empty($data['driver_id']) && (int) Driver::findOrFail($data['driver_id'])->company_id !== $companyId) {
                return back()->withInput()->withErrors(['driver_id' => 'راننده باید متعلق به شرکت انتخاب‌شده باشد.']);
            }
            if (! empty($data['fleet_id']) && (int) Fleet::findOrFail($data['fleet_id'])->company_id !== $companyId) {
                return back()->withInput()->withErrors(['fleet_id' => 'ناوگان باید متعلق به شرکت انتخاب‌شده باشد.']);
            }
        }

        $document = DB::transaction(function () use ($data) {
            $goods = $data['goods'];
            unset($data['goods']);
            $data['attached_documents'] = collect(preg_split('/\r\n|\r|\n|,/', $data['attached_documents_text'] ?? ''))
                ->map(fn ($item) => trim($item))->filter()->values()->all();
            unset($data['attached_documents_text']);
            $data['print_template_id'] = CmrPrintTemplate::where('company_id', $data['company_id'])
                ->where('is_default', true)->where('is_active', true)->value('id');
            $document = CmrDocument::create($data + ['uuid' => (string) Str::uuid(), 'status' => 'draft']);
            foreach ($goods as $index => $good) {
                $document->goods()->create($good + ['line_number' => $index + 1]);
            }
            CmrEvent::create([
                'cmr_document_id' => $document->id,
                'event_type' => 'created',
                'to_status' => 'draft',
                'actor_user_id' => auth()->id(),
                'actor_role' => 'admin',
                'description' => 'پیش‌نویس CMR ایجاد شد.',
                'occurred_at' => now(),
            ]);
            return $document;
        });

        return redirect()->route('admin.cmr.show', $document)->with('success', 'پیش‌نویس CMR ایجاد شد.');
    }

    public function show(CmrDocument $cmr)
    {
        $cmr->load(['company', 'driver', 'fleet', 'goods', 'events', 'walletEntries', 'versions', 'amendments', 'attachments', 'signatures']);
        return view('CMR.admin.show', compact('cmr'));
    }

    public function print(CmrDocument $cmr)
    {
        $cmr->load(['company', 'driver', 'fleet', 'goods', 'signatures']);
        return view('CMR.print.standard', compact('cmr'));
    }

    public function issue(CmrDocument $cmr, CmrIssuanceService $service)
    {
        try {
            $service->issue($cmr, (int) auth()->id());
            return back()->with('success', 'CMR با موفقیت صادر و هزینه آن ثبت شد.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['issue' => $exception->getMessage()]);
        }
    }

    public function cancel(Request $request, CmrDocument $cmr)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        try {
            DB::transaction(function () use ($cmr, $data) {
                $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
                if (! in_array($document->status, ['draft', 'issued'], true)) {
                    throw new \RuntimeException('این وضعیت سند قابل لغو نیست.');
                }

                $fromStatus = $document->status;
                $document->update(['status' => 'cancelled']);
                CmrEvent::create([
                    'cmr_document_id' => $document->id,
                    'event_type' => 'cancelled',
                    'from_status' => $fromStatus,
                    'to_status' => 'cancelled',
                    'actor_user_id' => auth()->id(),
                    'actor_role' => 'admin',
                    'description' => $data['reason'],
                    'metadata' => [
                        'refund_policy' => 'non_refundable',
                        'refunded_amount' => 0,
                        'original_issuance_fee' => (float) $document->issuance_fee,
                    ],
                    'occurred_at' => now(),
                ]);
            });
            return back()->with('success', 'CMR لغو شد. مطابق سیاست مالی، مبلغ صدور مسترد نشد.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['cancel' => $exception->getMessage()]);
        }
    }

    public function settings()
    {
        return view('CMR.admin.settings', [
            'settings' => CmrSetting::current(),
            'history' => CmrTariffHistory::query()->latest('effective_from')->limit(20)->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'issuance_fee' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_enabled' => ['nullable', 'boolean'],
        ]);
        $data['billing_enabled'] = $request->boolean('billing_enabled');
        DB::transaction(function () use ($data) {
            CmrSetting::current()->update($data);
            CmrTariffHistory::create($data + [
                'changed_by' => auth()->id(),
                'effective_from' => now(),
            ]);
        });
        return back()->with('success', 'تنظیمات مالی CMR ذخیره شد.');
    }
}

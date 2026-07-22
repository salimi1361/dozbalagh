<?php

namespace App\Http\Controllers\Association;

use App\Models\Country;
use App\Models\PermitPrintLayout;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrintLayoutController extends Controller
{
    public static function permitTypeLabels(): array
    {
        return [
            'bilateral' => 'دوجانبه',
            'transit' => 'ترانزیت',
            'bilateral_transit' => 'دوجانبه ترانزیت',
            'third_country_transit' => 'ترانزیت ثالث',
            'third_country' => 'ثالث',
        ];
    }

    public static function fieldCatalog(): array
    {
        return [
            'association_seal' => 'تصویر مهر انجمن',
            'chairman_signature' => 'تصویر امضای رئیس انجمن',
            'serial_number' => 'شماره دوزوله تخصیص‌یافته',
            'tracking_code' => 'کد رهگیری سامانه',
            'company_name' => 'نام شرکت (فارسی)',
            'company_name_en' => 'نام شرکت (لاتین)',
            'company_address' => 'نشانی شرکت (فارسی)',
            'company_address_en' => 'نشانی شرکت (لاتین)',
            'driver_name' => 'نام راننده',
            'driver_name_en' => 'نام راننده (لاتین)',
            'driver_passport' => 'شماره گذرنامه راننده',
            'vehicle_plate' => 'پلاک کشنده',
            'trailer_plate' => 'پلاک تریلر',
            'loading_origin' => 'مبدأ بارگیری',
            'loading_destination' => 'مقصد تخلیه',
            'permit_type' => 'نوع مجوز',
            'operation_type' => 'نوع عملیات حمل',
            'issued_date' => 'تاریخ صدور',
            'valid_until' => 'تاریخ پایان اعتبار',
            'country_name' => 'کشور مقصد',
            'cits_code' => 'کد CITS',
            'trip_code' => 'کد سفر',
        ];
    }

    public function index()
    {
        return view('association.print-layouts.index', [
            'layouts' => PermitPrintLayout::with('country')->latest()->get(),
            'countries' => Country::where('is_active', true)->orderBy('name')->get(),
            'permitTypeLabels' => self::permitTypeLabels(),
        ]);
    }

    public function create()
    {
        $countries = Country::where('is_active', true)->orderBy('name')->get();
        return view('association.print-layouts.editor', [
            'layout' => null,
            'countries' => $countries,
            'countryPermitTypes' => $this->countryPermitTypes($countries),
            'permitTypeLabels' => self::permitTypeLabels(),
            'catalog' => self::fieldCatalog(),
        ]);
    }

    public function edit(PermitPrintLayout $layout)
    {
        $layout->load(['fields', 'masks']);
        $countries = Country::where('is_active', true)->orderBy('name')->get();
        return view('association.print-layouts.editor', [
            'layout' => $layout,
            'countries' => $countries,
            'countryPermitTypes' => $this->countryPermitTypes($countries),
            'permitTypeLabels' => self::permitTypeLabels(),
            'catalog' => self::fieldCatalog(),
        ]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new PermitPrintLayout());
    }

    public function update(Request $request, PermitPrintLayout $layout)
    {
        return $this->persist($request, $layout);
    }

    public function destroy(PermitPrintLayout $layout)
    {
        $layout->delete();

        return redirect()
            ->route('association.print-layouts.index')
            ->with('success', 'قالب چاپ حذف شد.');
    }

    private function persist(Request $request, PermitPrintLayout $layout)
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'permit_type' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'paper_width_mm' => ['required', 'numeric', 'between:50,1000'],
            'paper_height_mm' => ['required', 'numeric', 'between:50,1000'],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'offset_x_mm' => ['required', 'numeric', 'between:-50,50'],
            'offset_y_mm' => ['required', 'numeric', 'between:-50,50'],
            'version' => ['required', 'integer', 'min:1'],
            'background' => ['nullable', 'image', 'max:15360'],
            'seal' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'fields_json' => ['required', 'json'],
            'masks_json' => ['required', 'json'],
        ]);

        $fields = json_decode($data['fields_json'], true, 512, JSON_THROW_ON_ERROR);
        $masks = json_decode($data['masks_json'], true, 512, JSON_THROW_ON_ERROR);
        $catalog = self::fieldCatalog();
        $country = Country::findOrFail($data['country_id']);
        if (!in_array($data['permit_type'], $country->allowed_permit_types ?? [], true)) {
            return back()->withInput()->withErrors(['permit_type' => 'نوع مجوز انتخاب‌شده برای این کشور تعریف نشده است.']);
        }

        DB::transaction(function () use ($request, $data, $fields, $masks, $catalog, $layout) {
            if ($request->hasFile('background')) {
                $data['background_path'] = $request->file('background')->store('permit-layouts', 'public');
            }
            if ($request->hasFile('seal')) {
                $data['seal_path'] = $request->file('seal')->store('permit-layout-assets', 'public');
            }
            if ($request->hasFile('signature')) {
                $data['signature_path'] = $request->file('signature')->store('permit-layout-assets', 'public');
            }
            $data['is_active'] = $request->boolean('is_active');
            $layout->fill(collect($data)->except(['background', 'seal', 'signature', 'fields_json', 'masks_json'])->all())->save();
            $layout->fields()->delete();
            $layout->masks()->delete();

            foreach ($fields as $index => $field) {
                if (!isset($catalog[$field['field_key'] ?? ''])) continue;
                $layout->fields()->create([
                    'field_key' => $field['field_key'], 'label' => $catalog[$field['field_key']],
                    'x_mm' => $field['x_mm'] ?? 10, 'y_mm' => $field['y_mm'] ?? 10,
                    'width_mm' => $field['width_mm'] ?? 50, 'height_mm' => $field['height_mm'] ?? 8,
                    'font_size_pt' => $field['font_size_pt'] ?? 11, 'font_family' => $field['font_family'] ?? 'Tahoma',
                    'text_align' => $field['text_align'] ?? 'center', 'rotation_deg' => $field['rotation_deg'] ?? 0,
                    'is_bold' => (bool)($field['is_bold'] ?? false),
                    'show_on_original' => (bool)($field['show_on_original'] ?? true),
                    'show_on_copy' => (bool)($field['show_on_copy'] ?? true), 'sort_order' => $index,
                ]);
            }
            foreach ($masks as $mask) {
                $layout->masks()->create([
                    'label' => $mask['label'] ?? 'پوشاندن شماره نمونه',
                    'x_mm' => $mask['x_mm'] ?? 10, 'y_mm' => $mask['y_mm'] ?? 10,
                    'width_mm' => $mask['width_mm'] ?? 40, 'height_mm' => $mask['height_mm'] ?? 8,
                    'color' => $mask['color'] ?? '#ffffff',
                ]);
            }
        });

        return redirect()->route('association.print-layouts.edit', $layout)->with('success', 'قالب چاپ ذخیره شد.');
    }

    private function countryPermitTypes($countries): array
    {
        return $countries->mapWithKeys(fn (Country $country) => [
            (string)$country->id => collect($country->allowed_permit_types ?? [])->map(fn ($type) => [
                'value' => $type,
                'label' => self::permitTypeLabels()[$type] ?? str_replace('_', ' ', $type),
            ])->values()->all(),
        ])->all();
    }
}

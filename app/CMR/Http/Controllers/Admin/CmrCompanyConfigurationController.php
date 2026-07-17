<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrPrintTemplate;
use App\CMR\Models\CmrSerialPool;
use App\CMR\Models\CmrSerial;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CmrCompanyConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::query()->orderBy('name_fa')->get();
        $company = $request->filled('company_id')
            ? $companies->firstWhere('id', (int) $request->integer('company_id'))
            : $companies->first();

        return view('CMR.admin.company-settings', [
            'companies' => $companies,
            'company' => $company,
            'settings' => $company ? CmrCompanySetting::forCompany($company->id) : null,
            'serialPools' => $company ? CmrSerialPool::where('company_id', $company->id)->latest()->get() : collect(),
            'templates' => $company ? CmrPrintTemplate::where('company_id', $company->id)->latest()->get() : collect(),
            'availableSerials' => $company ? CmrSerial::where('company_id', $company->id)->where('status', 'available')->latest()->limit(100)->get() : collect(),
            'availableSerialCount' => $company ? CmrSerial::where('company_id', $company->id)->where('status', 'available')->count() : 0,
            'availablePoolCount' => $company ? (int) CmrSerialPool::where('company_id', $company->id)
                ->where('is_active', true)
                ->selectRaw('COALESCE(SUM(GREATEST(range_end - next_number + 1, 0)), 0) AS remaining')
                ->value('remaining') : 0,
        ]);
    }

    public function updateSettings(Request $request, Company $company)
    {
        $data = $request->validate([
            'assignment_policy' => ['required', 'in:same_company,any_registered,authorized_external'],
            'print_language' => ['required', 'in:en'],
            'require_latin_data' => ['nullable', 'boolean'],
        ]);
        $data['require_latin_data'] = true;
        $data['serial_mode'] = 'pool';
        CmrCompanySetting::forCompany($company->id)->update($data);
        return back()->with('success', 'سیاست CMR شرکت ذخیره شد.');
    }

    public function storeSerialList(Request $request, Company $company)
    {
        $data = $request->validate(['serials' => ['required', 'string', 'max:20000']]);
        $serials = collect(preg_split('/[\r\n,;]+/', $data['serials']))->map(fn ($value) => trim($value))->filter()->unique();
        if ($serials->isEmpty()) {
            return back()->withErrors(['serials' => 'حداقل یک شماره معتبر وارد کنید.']);
        }
        DB::transaction(function () use ($serials, $company) {
            foreach ($serials as $serial) {
                CmrSerial::firstOrCreate(['company_id' => $company->id, 'serial' => $serial], ['status' => 'available']);
            }
            CmrCompanySetting::forCompany($company->id)->update(['serial_mode' => 'pool']);
        });
        return back()->with('success', $serials->count().' شماره رسمی شرکت ثبت شد.');
    }

    public function storeSerialPool(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:30'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'number_padding' => ['nullable', 'integer', 'min:1', 'max:20'],
            'range_start' => ['required', 'integer', 'min:1'],
            'range_end' => ['required', 'integer', 'gte:range_start'],
        ]);

        $data['number_padding'] = (int) ($data['number_padding'] ?? 1);

        $overlaps = CmrSerialPool::query()
            ->where('company_id', $company->id)
            ->where('prefix', $data['prefix'] ?? null)
            ->where('suffix', $data['suffix'] ?? null)
            ->where('is_active', true)
            ->where('range_start', '<=', $data['range_end'])
            ->where('range_end', '>=', $data['range_start'])
            ->exists();

        if ($overlaps) {
            return back()->withInput()->withErrors([
                'range_start' => 'این بازه با یکی از بازه‌های فعال همین شرکت هم‌پوشانی دارد.',
            ]);
        }

        CmrSerialPool::create($data + [
            'company_id' => $company->id,
            'next_number' => $data['range_start'],
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);
        return back()->with('success', 'بازه سریال CMR شرکت ثبت شد.');
    }

    public function storeTemplate(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'print_mode' => ['required', 'in:full,preprinted'],
            'header_mode' => ['required', 'in:company_profile,custom,none'],
            'custom_company_name' => ['nullable', 'string', 'max:255'],
            'custom_company_address' => ['nullable', 'string', 'max:2000'],
            'custom_company_contact' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        DB::transaction(function () use ($company, $data, $request) {
            $isDefault = $request->boolean('is_default');
            if ($isDefault) {
                CmrPrintTemplate::where('company_id', $company->id)->update(['is_default' => false]);
            }
            CmrPrintTemplate::create($data + [
                'company_id' => $company->id,
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'field_layout' => [],
                'is_default' => $isDefault,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        });
        return back()->with('success', 'قالب چاپ CMR شرکت ثبت شد.');
    }
}

<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrPrintTemplate;
use App\CMR\Models\CmrSerialPool;
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
        ]);
    }

    public function updateSettings(Request $request, Company $company)
    {
        $data = $request->validate([
            'assignment_policy' => ['required', 'in:same_company,any_registered,authorized_external'],
            'serial_mode' => ['required', 'in:system,manual,pool'],
            'print_language' => ['required', 'in:en'],
            'require_latin_data' => ['nullable', 'boolean'],
        ]);
        $data['require_latin_data'] = true;
        CmrCompanySetting::forCompany($company->id)->update($data);
        return back()->with('success', 'سیاست CMR شرکت ذخیره شد.');
    }

    public function storeSerialPool(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:30'],
            'range_start' => ['required', 'integer', 'min:1'],
            'range_end' => ['required', 'integer', 'gte:range_start'],
        ]);
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PanelFeatureService;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelFeatureController extends Controller
{
    public function index(PanelFeatureService $features): View
    {
        $rawTransferFinancial = SystemSetting::getValue('permit_unused_transfer_financial', '{}');
        $transferFinancial = is_array($rawTransferFinancial) ? $rawTransferFinancial : json_decode((string) $rawTransferFinancial, true);
        return view('admin.settings.panel_features', [
            'definitions' => $features->definitions(),
            'features' => $features,
            'transferFinancial' => array_merge(['enabled' => false], is_array($transferFinancial) ? $transferFinancial : []),
        ]);
    }

    public function update(Request $request, PanelFeatureService $features): RedirectResponse
    {
        $features->save($request->input('features', []));
        SystemSetting::setValue('permit_unused_transfer_financial', [
            'enabled' => $request->boolean('transfer_financial.enabled'),
        ]);

        return back()->with('success', 'دسترسی آیتم‌های پنل‌ها با موفقیت ذخیره شد.');
    }
}

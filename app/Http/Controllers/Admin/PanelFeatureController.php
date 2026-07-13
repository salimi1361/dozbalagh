<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PanelFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelFeatureController extends Controller
{
    public function index(PanelFeatureService $features): View
    {
        return view('admin.settings.panel_features', [
            'definitions' => $features->definitions(),
            'features' => $features,
        ]);
    }

    public function update(Request $request, PanelFeatureService $features): RedirectResponse
    {
        $features->save($request->input('features', []));

        return back()->with('success', 'دسترسی آیتم‌های پنل‌ها با موفقیت ذخیره شد.');
    }
}

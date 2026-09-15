<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FleetLoadingType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FleetLoadingTypeController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $isAssociation = $user?->hasRole('association');

        $query = FleetLoadingType::query();
        if ($isAssociation) {
            $query->where(function ($scope) use ($user) {
                $scope->where(function ($available) {
                    $available->where('is_active', true)->where('association_enabled', true);
                })->orWhere(function ($custom) use ($user) {
                    $custom->where('is_custom', true)->where('created_by_user_id', $user->id);
                });
            });
        }

        $types = $query->ordered()->get();

        return view('admin.fleet_loading_types.index', [
            'types' => $types,
            'isAssociation' => $isAssociation,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isAssociation = $user?->hasRole('association');
        $validated = $request->validate($this->rules());
        $name = $this->normalizeName($validated['name_fa']);

        if ($isAssociation) {
            FleetLoadingType::create([
                'rmto_code' => null,
                'name_fa' => $name,
                'min_weight_kg' => $validated['min_weight_kg'] ?? null,
                'max_weight_kg' => $validated['max_weight_kg'] ?? null,
                'vehicle_scope' => 'trailer',
                'is_international' => true,
                'is_active' => true,
                'association_enabled' => true,
                'is_custom' => true,
                'created_by_user_id' => $user->id,
                'sort_order' => 100000,
            ]);

            return back()->with('success', 'نوع بارگیر سفارشی برای انجمن اضافه شد.');
        }

        $code = trim((string) ($validated['rmto_code'] ?? '')) ?: null;
        FleetLoadingType::create([
            'rmto_code' => $code,
            'name_fa' => $name,
            'min_weight_kg' => $validated['min_weight_kg'] ?? null,
            'max_weight_kg' => $validated['max_weight_kg'] ?? null,
            'vehicle_scope' => $validated['vehicle_scope'],
            'is_international' => $request->boolean('is_international'),
            'is_active' => $request->boolean('is_active'),
            'association_enabled' => $request->boolean('association_enabled'),
            'is_custom' => $code === null,
            'created_by_user_id' => $user->id,
            'sort_order' => 100000,
        ]);

        return back()->with('success', 'نوع بارگیر جدید با موفقیت اضافه شد.');
    }

    public function update(Request $request, FleetLoadingType $fleetLoadingType): RedirectResponse
    {
        $user = auth()->user();
        $isAssociation = $user?->hasRole('association');

        if ($isAssociation) {
            abort_unless($fleetLoadingType->is_custom && (int) $fleetLoadingType->created_by_user_id === (int) $user->id, 403);
            $validated = $request->validate([
                'name_fa' => ['required', 'string', 'max:255'],
                'min_weight_kg' => ['nullable', 'integer', 'min:0', 'max:10000000'],
                'max_weight_kg' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            ]);
            $fleetLoadingType->update([
                'name_fa' => $this->normalizeName($validated['name_fa']),
                'min_weight_kg' => $validated['min_weight_kg'] ?? null,
                'max_weight_kg' => $validated['max_weight_kg'] ?? null,
            ]);

            return back()->with('success', 'نوع بارگیر سفارشی انجمن به‌روزرسانی شد.');
        }

        $validated = $request->validate($this->rules($fleetLoadingType));
        $code = trim((string) ($validated['rmto_code'] ?? '')) ?: null;
        $fleetLoadingType->update([
            'rmto_code' => $code,
            'name_fa' => $this->normalizeName($validated['name_fa']),
            'min_weight_kg' => $validated['min_weight_kg'] ?? null,
            'max_weight_kg' => $validated['max_weight_kg'] ?? null,
            'vehicle_scope' => $validated['vehicle_scope'],
            'is_international' => $request->boolean('is_international'),
            'is_active' => $request->boolean('is_active'),
            'association_enabled' => $request->boolean('association_enabled'),
            'is_custom' => $code === null,
        ]);

        return back()->with('success', 'تنظیمات نوع بارگیر ذخیره شد.');
    }

    public function destroy(FleetLoadingType $fleetLoadingType): RedirectResponse
    {
        $user = auth()->user();
        if ($user?->hasRole('association')) {
            abort_unless($fleetLoadingType->is_custom && (int) $fleetLoadingType->created_by_user_id === (int) $user->id, 403);
        }

        if ($fleetLoadingType->fleets()->exists()) {
            return back()->with('error', 'این نوع بارگیر در پرونده ناوگان استفاده شده و قابل حذف نیست؛ آن را غیرفعال کنید.');
        }

        $fleetLoadingType->delete();

        return back()->with('success', 'نوع بارگیر حذف شد.');
    }

    private function rules(?FleetLoadingType $type = null): array
    {
        $codeRule = Rule::unique('fleet_loading_types', 'rmto_code');
        if ($type) {
            $codeRule = $codeRule->ignore($type->id);
        }

        return [
            'name_fa' => ['required', 'string', 'max:255'],
            'rmto_code' => ['nullable', 'string', 'max:50', $codeRule],
            'min_weight_kg' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'max_weight_kg' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'vehicle_scope' => ['required', Rule::in(['truck', 'trailer', 'special', 'other'])],
        ];
    }

    private function normalizeName(string $value): string
    {
        return trim(strtr(preg_replace('/\s+/u', ' ', $value), [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ۀ' => 'ه',
            'ة' => 'ه',
        ]));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DozbalaghBatch;
use App\Models\DozbalaghItem;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // ۱. نمایش لیست، آمار کلی، و جستجو
    public function index(Request $request)
    {
        $search = $request->input('search');

        // دریافت لیست دسته‌ها با اعمال فیلتر جستجو
        $batches = DozbalaghBatch::with('country')
            ->when($search, function($query) use ($search) {
                $query->where('country_name', 'like', "%$search%");
            })
            ->orderBy('id', 'desc')
            ->get();

        // محاسبه دقیق و هوشمند آمار مصرف و مانده انبار
        foreach ($batches as $batch) {
            $batch->consumed_count = DB::table('dozbalagh_items')
                ->where('batch_id', $batch->id)
                ->whereNotNull('lifecycle_status')
                ->whereNotIn('lifecycle_status', ['raw', 'in_stock'])
                ->count();

            $batch->remaining_count = max(0, $batch->total_quantity - $batch->consumed_count);
        }

        // آمار کلی انبار برای کارت‌های بالای صفحه
        $stats = DozbalaghItem::select('lifecycle_status', DB::raw('count(*) as total'))
                    ->groupBy('lifecycle_status')
                    ->pluck('total', 'lifecycle_status');

        $countries = Country::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.inventory.index', compact('batches', 'countries', 'stats', 'search'));
    }

    // 🟢 متد جدید: دریافت لیست شماره سریال‌های مصرف نشده یک پارت به صورت Ajax
    public function getAvailableSerials($id)
    {
        try {
            $serials = DB::table('dozbalagh_items')
                ->where('batch_id', $id)
                ->where(function($query) {
                    $query->whereNull('lifecycle_status')
                          ->orWhere('lifecycle_status', 'raw')
                          ->orWhere('lifecycle_status', 'in_stock');
                })
                ->orderBy('serial_number', 'asc')
                ->pluck('serial_number');

            return response()->json([
                'success' => true,
                'serials' => $serials
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات: ' . $e->getMessage()
            ], 500);
        }
    }

    // ۲. تولید انبوه سریال‌ها
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'permit_type' => 'required|string|max:100',
            'serial_start' => 'required|integer|min:1',
            'serial_end' => 'required|integer|gte:serial_start',
            'received_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:received_date',
        ]);

        $country = Country::findOrFail($validated['country_id']);
        $allowedPermitTypes = $country->allowed_permit_types ?? [];
        if (! in_array($validated['permit_type'], $allowedPermitTypes, true)) {
            return back()
                ->withInput()
                ->withErrors(['permit_type' => 'این نوع مجوز برای کشور انتخاب‌شده در مدیریت کشورها تعریف نشده است.']);
        }

        $totalQuantity = $validated['serial_end'] - $validated['serial_start'] + 1;

        if($totalQuantity > 50000) {
            return back()->withErrors(['serial_end' => 'تعداد در هر مرحله نباید بیش از 50,000 باشد.']);
        }

        $hasOverlappingSerial = DB::table('dozbalagh_items')
            ->where('country_id', $validated['country_id'])
            ->where('permit_type', $validated['permit_type'])
            ->whereBetween('serial_number', [$validated['serial_start'], $validated['serial_end']])
            ->exists();

        if ($hasOverlappingSerial) {
            return back()
                ->withInput()
                ->withErrors(['serial_start' => 'بخشی از این بازه قبلاً برای همین کشور و نوع مجوز در انبار ثبت شده است.']);
        }

        DB::beginTransaction();
        try {
            $batch = DozbalaghBatch::create([
                'country_id' => $validated['country_id'],
                'permit_type' => $validated['permit_type'],
                'country_name' => $country->name,
                'serial_start' => $validated['serial_start'],
                'serial_end' => $validated['serial_end'],
                'total_quantity' => $totalQuantity,
                'received_date' => $validated['received_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'status' => 'active'
            ]);

            $items = [];
            $now = now();
            for ($i = $validated['serial_start']; $i <= $validated['serial_end']; $i++) {
                $items[] = [
                    'batch_id' => $batch->id,
                    'country_id' => $batch->country_id,
                    'permit_type' => $batch->permit_type,
                    'serial_number' => $i,
                    'lifecycle_status' => 'raw',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($items, 1000) as $chunk) {
                DozbalaghItem::insert($chunk);
            }

            DB::commit();
            return back()->with('success', 'تعداد ' . $totalQuantity . ' دوزبلاغ با موفقیت تولید شد.');
            
        } catch (QueryException $e) {
            DB::rollBack();
            if ((string) $e->getCode() === '23000' || str_contains($e->getMessage(), '1062')) {
                return back()
                    ->withInput()
                    ->withErrors(['serial_start' => 'بخشی از این بازه هم‌اکنون برای همین کشور و نوع مجوز ثبت شده است.']);
            }

            return back()->withErrors(['error' => 'خطا در ثبت موجودی. لطفاً دوباره تلاش کنید.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت موجودی. لطفاً دوباره تلاش کنید.']);
        }
    }

    // ۳. ویرایش کشور/نوع مجوز پارت برای اصلاح پارت‌های قدیمی یا ثبت‌شده اشتباه
    public function edit($id)
    {
        $batch = DozbalaghBatch::with('country')->findOrFail($id);
        $countries = Country::where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory.edit', compact('batch', 'countries'));
    }

    public function update(Request $request, $id)
    {
        $batch = DozbalaghBatch::findOrFail($id);
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'permit_type' => 'required|string|max:100',
            'received_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:received_date',
        ]);

        $country = Country::findOrFail($validated['country_id']);
        if (! in_array($validated['permit_type'], $country->allowed_permit_types ?? [], true)) {
            return back()
                ->withInput()
                ->withErrors(['permit_type' => 'این نوع مجوز برای کشور انتخاب‌شده در مدیریت کشورها تعریف نشده است.']);
        }

        $usedItemsCount = DB::table('dozbalagh_items')
            ->where('batch_id', $batch->id)
            ->whereNotNull('lifecycle_status')
            ->whereNotIn('lifecycle_status', ['raw', 'in_stock'])
            ->count();

        $countryChanged = (int) $batch->country_id !== (int) $validated['country_id'];
        $permitTypeChanged = (string) $batch->permit_type !== (string) $validated['permit_type'];
        if ($usedItemsCount > 0 && ($countryChanged || $permitTypeChanged)) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'این پارت دارای دوزوله تخصیص‌یافته یا مصرف‌شده است و کشور/نوع مجوز آن قابل تغییر نیست.']);
        }

        $hasOverlappingSerial = DB::table('dozbalagh_items')
            ->where('batch_id', '<>', $batch->id)
            ->where('country_id', $validated['country_id'])
            ->where('permit_type', $validated['permit_type'])
            ->whereBetween('serial_number', [$batch->serial_start, $batch->serial_end])
            ->exists();

        if ($hasOverlappingSerial) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'بخشی از شماره‌های این پارت برای همین کشور و نوع مجوز قبلاً در انبار ثبت شده است.']);
        }

        try {
            DB::transaction(function () use ($batch, $validated, $country): void {
                $batch->update([
                    'country_id' => $validated['country_id'],
                    'permit_type' => $validated['permit_type'],
                    'country_name' => $country->name,
                    'received_date' => $validated['received_date'],
                    'expiry_date' => $validated['expiry_date'] ?? null,
                ]);

                DB::table('dozbalagh_items')
                    ->where('batch_id', $batch->id)
                    ->update([
                        'country_id' => $validated['country_id'],
                        'permit_type' => $validated['permit_type'],
                        'updated_at' => now(),
                    ]);
            });
        } catch (QueryException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'بخشی از شماره‌های این پارت برای همین کشور و نوع مجوز قبلاً ثبت شده است.']);
        }

        return redirect()->route('admin.inventory.index')
            ->with('success', 'کشور و نوع مجوز پارت با موفقیت اصلاح شد.');
    }

    // ۴. حذف پارت
    public function destroy($id)
    {
        $batch = DozbalaghBatch::findOrFail($id);

        $usedItemsCount = DB::table('dozbalagh_items')
            ->where('batch_id', $batch->id)
            ->whereNotNull('lifecycle_status')
            ->whereNotIn('lifecycle_status', ['raw', 'in_stock'])
            ->count();

        if ($usedItemsCount > 0) {
            return back()->withErrors(['error' => 'خطا: از این پارت ' . $usedItemsCount . ' عدد دوزبلاغ به انجمن تخصیص داده شده یا مصرف شده است. امکان حذف وجود ندارد!']);
        }

        DB::beginTransaction();
        try {
            DB::table('dozbalagh_items')->where('batch_id', $batch->id)->delete();
            $batch->delete();

            DB::commit();
            return back()->with('success', 'پارت #' . $id . ' و تمام سریال‌های آن با موفقیت از انبار حذف شدند.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف سیستم: ' . $e->getMessage()]);
        }
    }
}

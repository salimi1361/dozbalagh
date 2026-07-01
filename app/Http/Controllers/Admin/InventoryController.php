<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DozbalaghBatch;
use App\Models\DozbalaghItem;
use App\Models\Country;
use Illuminate\Http\Request;
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

        // محاسبه آمار مصرف برای هر پارت به صورت جداگانه
        foreach ($batches as $batch) {
            $batch->remaining_count = $batch->items()->where('lifecycle_status', 'raw')->count();
            $batch->consumed_count = $batch->items()->where('lifecycle_status', 'consumed')->count();
        }

        // آمار کلی انبار برای کارت‌های بالای صفحه
        $stats = DozbalaghItem::select('lifecycle_status', DB::raw('count(*) as total'))
                    ->groupBy('lifecycle_status')
                    ->pluck('total', 'lifecycle_status');

        $countries = Country::where('is_active', true)->get();
        
        return view('admin.inventory.index', compact('batches', 'countries', 'stats', 'search'));
    }

    // ۲. تولید انبوه سریال‌ها
    public function store(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'serial_start' => 'required|integer|min:1',
            'serial_end' => 'required|integer|gte:serial_start',
            'expiry_date' => 'nullable|date',
        ]);

        $country = Country::find($request->country_id);
        $totalQuantity = $request->serial_end - $request->serial_start + 1;

        if($totalQuantity > 50000) {
            return back()->withErrors(['serial_end' => 'تعداد در هر مرحله نباید بیش از 50,000 باشد.']);
        }

        DB::beginTransaction();
        try {
            $batch = DozbalaghBatch::create([
                'country_id' => $request->country_id,
                'country_name' => $country->name,
                'serial_start' => $request->serial_start,
                'serial_end' => $request->serial_end,
                'total_quantity' => $totalQuantity,
                'expiry_date' => $request->expiry_date,
                'status' => 'active'
            ]);

            $items = [];
            $now = now();
            for ($i = $request->serial_start; $i <= $request->serial_end; $i++) {
                $items[] = [
                    'batch_id' => $batch->id,
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
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطای سیستمی: ' . $e->getMessage()]);
        }
    }

    // ۳. حذف پارت (فقط در صورتی که هیچ دوزبلاغی مصرف یا توزیع نشده باشد)
    public function destroy($id)
    {
        $batch = DozbalaghBatch::findOrFail($id);

        // بررسی امنیتی: آیا از این پارت دوزبلاغی از حالت "خام" (raw) خارج شده است؟
        $usedItemsCount = $batch->items()->where('lifecycle_status', '!=', 'raw')->count();

        if ($usedItemsCount > 0) {
            return back()->withErrors(['error' => 'خطا: از این پارت ' . $usedItemsCount . ' عدد دوزبلاغ به انجمن تخصیص داده شده یا مصرف شده است. امکان حذف وجود ندارد!']);
        }

        DB::beginTransaction();
        try {
            // ۱. اول تمام سریال‌های زیرمجموعه در جدول items پاک می‌شوند
            $batch->items()->delete();
            
            // ۲. سپس خود شناسنامه پارت پاک می‌شود
            $batch->delete();

            DB::commit();
            return back()->with('success', 'پارت #' . $id . ' و تمام سریال‌های آن با موفقیت از انبار حذف شدند.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف سیستم: ' . $e->getMessage()]);
        }
    }
}
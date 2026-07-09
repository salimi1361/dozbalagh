<?php

namespace App\Http\Controllers\Association;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Throwable;

class AssociationController
{
    public function dashboard()
    {
        try {
            $today = Carbon::now()->startOfDay();
            $lastWeekStart = Carbon::now()->subDays(6)->startOfDay();
            $lastMonthStart = Carbon::now()->subDays(29)->startOfDay();

            $stats = [
                'companies' => DB::table('companies')->count(),
                'pending' => DB::table('permit_requests')->where('status', 'pending')->count(),
                'approved_waiting_serial' => DB::table('permit_requests')
                    ->where('status', 'approved')
                    ->whereNull('serial_number')
                    ->count(),
                'issued' => DB::table('permit_requests')->where('status', 'issued')->count(),
                'events_today' => DB::table('driver_events')->where('created_at', '>=', $today)->count(),
                'events_week' => DB::table('driver_events')->where('created_at', '>=', $lastWeekStart)->count(),
                'active_drivers_week' => DB::table('driver_events')
                    ->where('created_at', '>=', $lastWeekStart)
                    ->distinct('driver_id')
                    ->count('driver_id'),
                'requests_week' => DB::table('permit_requests')->where('created_at', '>=', $lastWeekStart)->count(),
            ];

            $dailyRequests = DB::table('permit_requests')
                ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
                ->where('created_at', '>=', $lastWeekStart)
                ->groupBy('day_key')
                ->pluck('total', 'day_key');

            $dailyEvents = DB::table('driver_events')
                ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
                ->where('created_at', '>=', $lastWeekStart)
                ->groupBy('day_key')
                ->pluck('total', 'day_key');

            $weekdays = [
                0 => 'یکشنبه',
                1 => 'دوشنبه',
                2 => 'سه‌شنبه',
                3 => 'چهارشنبه',
                4 => 'پنجشنبه',
                5 => 'جمعه',
                6 => 'شنبه',
            ];

            $trendLabels = [];
            $requestTrend = [];
            $eventTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $key = $date->toDateString();
                $trendLabels[] = $weekdays[$date->dayOfWeek] ?? $date->format('Y/m/d');
                $requestTrend[] = (int) ($dailyRequests[$key] ?? 0);
                $eventTrend[] = (int) ($dailyEvents[$key] ?? 0);
            }

            $eventTypeLabels = $this->driverEventTypeLabels();
            $eventRows = DB::table('driver_events')
                ->select('event_type', DB::raw('COUNT(*) as total'))
                ->where('created_at', '>=', $lastMonthStart)
                ->groupBy('event_type')
                ->orderByDesc('total')
                ->limit(6)
                ->get();

            $eventTypeChart = [
                'labels' => $eventRows->map(fn ($row) => $eventTypeLabels[$row->event_type] ?? $row->event_type)->values(),
                'series' => $eventRows->map(fn ($row) => (int) $row->total)->values(),
            ];

            $destinationRows = DB::table('permit_request_items as pri')
                ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
                ->selectRaw("COALESCE(c.name, 'نامشخص') as country_name, COUNT(*) as total")
                ->groupBy('country_name')
                ->orderByDesc('total')
                ->limit(6)
                ->get();

            $destinationChart = [
                'labels' => $destinationRows->pluck('country_name')->values(),
                'series' => $destinationRows->map(fn ($row) => (int) $row->total)->values(),
            ];

            $statusLabels = $this->permitStatusLabels();
            $statusCards = DB::table('permit_requests')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => [
                    'label' => $statusLabels[$row->status] ?? ($row->status ?: 'نامشخص'),
                    'value' => (int) $row->total,
                    'key' => $row->status ?: 'unknown',
                ]);

            $companyEventLeaders = DB::table('driver_events as de')
                ->leftJoin('dozbalagh_items as di', 'de.dozbalagh_item_id', '=', 'di.id')
                ->leftJoin('permit_requests as pr', 'di.serial_number', '=', 'pr.serial_number')
                ->leftJoin('companies as c', 'di.company_id', '=', 'c.id')
                ->leftJoin('companies as pc', 'pr.company_id', '=', 'pc.id')
                ->selectRaw("COALESCE(c.name_fa, c.name, pc.name_fa, pc.name, 'بدون شرکت') as company_name, COUNT(de.id) as total")
                ->where('de.created_at', '>=', $lastMonthStart)
                ->groupBy('company_name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $latestEvents = DB::table('driver_events as de')
                ->leftJoin('drivers as d', 'de.driver_id', '=', 'd.id')
                ->leftJoin('dozbalagh_items as di', 'de.dozbalagh_item_id', '=', 'di.id')
                ->leftJoin('permit_requests as pr', 'di.serial_number', '=', 'pr.serial_number')
                ->leftJoin('companies as c', 'di.company_id', '=', 'c.id')
                ->leftJoin('companies as pc', 'pr.company_id', '=', 'pc.id')
                ->select([
                    'de.id',
                    'de.event_type',
                    'de.latitude',
                    'de.longitude',
                    'de.created_at',
                    'di.serial_number',
                    'pr.d_code',
                    'c.name_fa as company_name_fa',
                    'c.name as company_name',
                    'pc.name_fa as permit_company_name_fa',
                    'pc.name as permit_company_name',
                    'd.first_name_fa',
                    'd.last_name_fa',
                    'd.national_code',
                ])
                ->orderByDesc('de.created_at')
                ->limit(8)
                ->get()
                ->map(function ($event) use ($eventTypeLabels) {
                    $driverName = trim(($event->first_name_fa ?? '') . ' ' . ($event->last_name_fa ?? ''));
                    $event->event_label = $eventTypeLabels[$event->event_type] ?? $event->event_type;
                    $event->driver_name = $driverName !== '' ? $driverName : 'راننده نامشخص';
                    $event->company_display = $event->company_name_fa
                        ?: ($event->company_name ?: ($event->permit_company_name_fa ?: ($event->permit_company_name ?: 'بدون شرکت')));
                    return $event;
                });

            $lowStockCountries = DB::table('dozbalagh_items as di')
                ->join('dozbalagh_batches as db', 'di.batch_id', '=', 'db.id')
                ->join('countries as c', 'db.country_id', '=', 'c.id')
                ->where(function ($query) {
                    $query->whereNull('di.lifecycle_status')
                        ->orWhere('di.lifecycle_status', 'raw')
                        ->orWhereNotIn('di.lifecycle_status', ['consumed', 'issued', 'collected', 'archived', 'lost']);
                })
                ->selectRaw('c.name as country_name, COUNT(di.id) as available_count')
                ->groupBy('c.name')
                ->having('available_count', '<=', 50)
                ->orderBy('available_count')
                ->limit(4)
                ->get();

            return view('association.dashboard', compact(
                'stats',
                'trendLabels',
                'requestTrend',
                'eventTrend',
                'eventTypeChart',
                'destinationChart',
                'statusCards',
                'companyEventLeaders',
                'latestEvents',
                'lowStockCountries'
            ));
        } catch (Throwable $e) {
            Log::error('Association Dashboard Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری داشبورد انجمن: ' . $e->getMessage());
        }
    }

    /**
     * نمایش لیست درخواست‌های منتظر بررسی (Pending) در کارتابل انجمن
     */
    public function index()
    {
        try {
            $requests = DB::table('permit_requests')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_id', $req->fleet_id)->first()
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }
                
                $destinations = DB::table('permit_request_items')
                    ->where('permit_request_id', $req->id)
                    ->get();
                
                $countryNames = [];
                foreach ($destinations as $dest) {
                    $countryInfo = DB::table('countries')->where('id', $dest->country_id)->first();
                    if ($countryInfo) {
                        $countryNames[] = $countryInfo->name;
                    }
                }
                $req->country_name = !empty($countryNames) ? implode('، ', $countryNames) : 'نامشخص';
            }
            
            return view('association.driver.index', compact('requests'));
        } catch (Throwable $e) {
            Log::error('Association Index Error: ' . $e->getMessage());
            return abort(500, 'خطا در کارتابل انجمن: ' . $e->getMessage());
        }
    }

   /**
     * واکشی اطلاعات و مستندات ارسالی شرکت به صورت JSON برای پاپ‌آپ بررسی انجمن
     * (نسخه اصلاح شده: شامل اطلاعات راننده، ناوگان و فیلدهای جدید مایگریشن)
     */
    /**
     * واکشی اطلاعات و مستندات ارسالی شرکت به صورت JSON برای پاپ‌آپ بررسی انجمن
     */
    /**
     * واکشی اطلاعات و مستندات ارسالی شرکت به صورت JSON برای پاپ‌آپ بررسی انجمن
     * (اصلاحیه فوق فنی: خواندن مستقیم اطلاعات گام دوم از جدول permit_request_items)
     */
    public function getRequestDetailsJson($id)
    {
        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return response()->json(['success' => false, 'message' => 'پرونده یافت نشد.'], 404);
            }

            // واکشی اولین ردیف جزئیات گام دوم از جدول واسط شرکت جهت استخراج فیلدها و اسناد
            $dbDetails = DB::table('permit_request_items')->where('permit_request_id', $permit->id)->first();

            // واکشی مشخصات راننده
            $driver = null;
            if (isset($permit->driver_id)) {
                $driver = DB::table('drivers')->where('id', $permit->driver_id)->first() 
                            ?? DB::table('drivers')->where('national_code', $permit->driver_id)->first();
            }

            // واکشی مشخصات ناوگان
            $fleet = null;
            if (isset($permit->fleet_id)) {
                $fleet = DB::table('fleets')->where('id', $permit->fleet_id)->first() 
                           ?? DB::table('fleets')->where('smart_card_number', $permit->fleet_id)->first();
            }

            // واکشی کشورهای مسیر سفر
            $items = DB::table('permit_request_items')
                ->where('permit_request_id', $permit->id)
                ->get();

            $destinations = [];
            foreach ($items as $item) {
                $country = DB::table('countries')->where('id', $item->country_id)->first();
                $destinations[] = [
                    'country_name' => $country ? $country->name : 'نامشخص',
                    'permit_type'  => $item->permit_type ?? '---'
                ];
            }

            return response()->json([
                'success' => true,
                'permit' => $permit,
                'dbDetails' => $dbDetails, // ارسال مستقل جزئیات گام دوم شرکت
                'driver' => $driver,
                'fleet'  => $fleet,
                'destinations' => $destinations
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'خطا در دریافت اطلاعات: ' . $e->getMessage()], 500);
        }
    }

    /**
     * متد ذخیره‌سازی ویرایش و تغییرات آنی اعمال شده توسط اپراتور انجمن (شامل متون و تاریخ‌ها)
     */
    public function updateCompanyRequestDataInline(Request $request, $id)
    {
        try {
            DB::table('permit_request_items')->where('permit_request_id', $id)->update([
                'operation_type'      => $request->input('cargo_type'),
                'loading_origin'      => $request->input('loading_origin'),
                'loading_destination' => $request->input('loading_destination'),
                'cits_code'           => $request->input('cits_code'),
                'trip_code'           => $request->input('trip_code'),
                'receipt_code'        => $request->input('receipt_code'),
                'cmr_date'            => $request->input('cmr_date'), // 🟢 ذخیره تاریخ CMR
                'tir_carnet_number'   => $request->input('tir_carnet_number'),
                'tir_carnet_date'     => $request->input('tir_carnet_date'), // 🟢 ذخیره تاریخ کارنه تیر
            ]);

            return response()->json(['success' => true, 'message' => 'تغییرات مستندات و تاریخ‌ها با موفقیت در پرونده ثبت شد.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'خطا در ذخیره ویرایش: ' . $e->getMessage()], 500);
        }
    }

    /**
     * مدیریت دکمه‌های تغییر وضعیت سریع و ثبت ردیابی تاریخ و ساعت اتمیک
     */
    public function updateRequestStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'reject_reason' => 'nullable|string',
            'courier_code' => 'nullable|string',
            'image' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            $now = Carbon::now();
            $userId = auth()->id() ?? null;
            $status = $request->input('status');

            // 🛑 بررسی امنیتی جدید: جلوگیری از ثبت وضعیت برگشت برای اصلاح
            if ($status === 'returned') {
                return response()->json(['success' => false, 'message' => 'عملیات برگشت برای اصلاح از سیستم حذف شده است. لطفاً از گزینه رد کامل استفاده کنید.'], 422);
            }

            $updateData = [
                'status' => $status,
                'reject_reason' => $request->input('reject_reason') ?? null,
                'action_by_user_id' => $userId,
                'updated_at' => $now
            ];

            if ($status === 'rejected') {
                $updateData['rejected_at'] = $now;
                $updateData['payment_status'] = 'refunded';

                $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
                $shouldReleaseFunds = $wallet
                    && ($permit->payment_status ?? null) === 'reserved'
                    && ($permit->status ?? null) !== 'rejected'
                    && (float) $permit->total_amount > 0
                    && (float) $wallet->blocked_balance > 0;

                if ($shouldReleaseFunds) {
                    $releaseAmount = min((float) $permit->total_amount, (float) $wallet->blocked_balance);

                    DB::table('wallets')->where('id', $wallet->id)->update([
                        'blocked_balance' => max(0, (float) $wallet->blocked_balance - $releaseAmount),
                        'balance' => (float) $wallet->balance + $releaseAmount,
                        'updated_at' => $now
                    ]);

                    // 🟢 فیکس قطعی: استفاده از مقدار دقیق تعریف شده در ENUM دیتابیس شما (dozbalagh_refund)
                    DB::table('wallet_transactions')->insert([
                        'wallet_id' => $wallet->id,
                        'amount' => $releaseAmount,
                        'type' => 'credit',
                        'action_type' => 'dozbalagh_refund', // 👈 منطبق بر ساختار ENUM دیتابیس شما
                        'description' => "برگشت کل وجه به دلیل رد درخواست دوزوله پرونده {$permit->d_code}",
                        'transaction_month' => $now->format('Y-m'),
                        'created_at' => $now, 
                        'updated_at' => $now
                    ]);
                }
            } 
            elseif ($status === 'approved') {
                $updateData['approved_at'] = $now;
            }
            elseif ($status === 'collected' || $status === 'lost' || $status === 'archived') {
                if ($status === 'collected' || $status === 'archived') {
                    if (empty($permit->company_return_image) || empty($permit->courier_delivery_code)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'شرکت هنوز لاشه و مشخصات پیک را برای این پرونده ثبت نکرده است.'
                        ], 422);
                    }

                    if (trim((string) $request->input('courier_code')) !== trim((string) $permit->courier_delivery_code)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'کد تحویل پیک صحیح نیست.'
                        ], 422);
                    }

                    $updateData['courier_received_at'] = $now;
                    $updateData['courier_received_by_user_id'] = $userId;
                    $updateData['collected_image'] = $permit->company_return_image;
                }
                
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $serialClean = $permit->serial_number ?? 'serial';
                    $fileName = $serialClean . '-' . $permit->d_code . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('permits/collected', $fileName, 'public');
                    $updateData['collected_image'] = 'permits/collected/' . $fileName;
                }

                if (Schema::hasColumn('permit_requests', 'closed_at')) {
                    $updateData['closed_at'] = $now;
                }

                if (isset($permit->driver_id) && Schema::hasColumn('drivers', 'is_blocked')) {
                    DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->update(['is_blocked' => false]);
                }
                if (isset($permit->fleet_id) && Schema::hasColumn('fleets', 'is_blocked')) {
                    DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->update(['is_blocked' => false]);
                }

                try {
                    if (isset($permit->serial_number)) {
                        DB::table('dozbalagh_items')
                            ->where('serial_number', $permit->serial_number)
                            ->update([
                                'lifecycle_status' => $status,
                                'returned_at'      => $now,
                                'updated_at'       => $now
                            ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Dozbalagh Items Warehouse Update Bypassed: ' . $e->getMessage());
                }
            }

            DB::table('permit_requests')->where('id', $id)->update($updateData);

            DB::commit();
            
            $messages = [
                'approved' => 'درخواست تایید اولیه شد و به کارتابل تخصیص سریال انتقال یافت.',
                'rejected' => 'درخواست رد و وجه با موفقیت عودت داده شد.',
                'collected' => 'تصویر لاشه دوزوله با موفقیت فشرده، بایگانی و ناوگان آزاد شد.',
                'archived' => 'لاشه پروانه با موفقیت تحویل گرفته شد و پرونده به بایگانی کل منتقل گردید.',
                'lost' => 'وضعیت مفقودی ثبت و راننده آزاد شد.'
            ];

            return response()->json(['success' => true, 'message' => $messages[$status] ?? 'عملیات با موفقیت انجام شد.'] );
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Update Request Status Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطا در پردازش عملیات مالی و اداری: ' . $e->getMessage()], 500);
        }
    }

   /**
     * بارگذاری لیست دوزوله‌های تایید اولیه شده که منتظر درج شماره سریال و چاپ هستند
     */
    public function associationApprovedPermits()
    {
        try {
            $requests = DB::table('permit_requests')
                ->where('status', 'approved')
                ->whereNull('serial_number')
                ->orderBy('id', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }
                
                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                
                $req->country_name = 'نامشخص';
                $req->next_serial_in_warehouse = 'بدون موجودی';
                $req->is_renewal = (($req->request_type ?? null) === 'renewal');
                $req->renewal_serial_number = $req->is_renewal ? ($req->previous_serial_number ?? $req->previous_d_code ?? null) : null;
                
                $req->validity_days = 30; // پیش‌فرض
                $req->expire_date_jalali = '---';

                if ($req->is_renewal && !empty($req->renewal_serial_number)) {
                    // در تمدید، برگه خام جدید از انبار مصرف نمی‌شود و همان شماره قبلی نمایش داده می‌شود.
                    $req->next_serial_in_warehouse = $req->renewal_serial_number;
                }

                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                        $req->validity_days = $countryInfo->validity_days ?? 30;
                        
                        $expireDate = now()->addDays($req->validity_days);
                        try {
                            $req->expire_date_jalali = \Morilog\Jalali\Jalalian::fromCarbon($expireDate)->format('Y/m/d');
                        } catch (\Exception $e) {
                            $req->expire_date_jalali = $expireDate->format('Y-m-d');
                        }
                    }

                    // برای درخواست جدید، اولین سریال آزاد از انبار پیشنهاد می‌شود.
                    // برای تمدید، نباید حتی پیشنهاد انبار جایگزین سریال قبلی شود.
                    if (!$req->is_renewal) {
                        $nextAvailableItem = DB::table('dozbalagh_items')
                            ->join('dozbalagh_batches', 'dozbalagh_items.batch_id', '=', 'dozbalagh_batches.id')
                            ->where('dozbalagh_batches.country_id', $destination->country_id)
                            ->where(function($query) {
                                $query->whereNull('dozbalagh_items.lifecycle_status')
                                      ->orWhere('dozbalagh_items.lifecycle_status', 'raw')
                                      ->orWhereNotIn('dozbalagh_items.lifecycle_status', ['consumed', 'issued', 'collected', 'archived', 'lost']);
                            })
                            ->orderBy('dozbalagh_items.serial_number', 'asc')
                            ->select('dozbalagh_items.serial_number')
                            ->first();

                        if ($nextAvailableItem) {
                            $req->next_serial_in_warehouse = $nextAvailableItem->serial_number;
                        }
                    }
                }
            }

            return view('association.driver.approved', compact('requests'));
        } catch (Throwable $e) {
            Log::error('Association Approved Permits Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری کارتابل صدور: ' . $e->getMessage());
        }
    }

   /**
     * صدور دوزوله:
     * - درخواست جدید: کسر برگه خام از انبار و ثبت سریال دستی
     * - تمدید: بدون مصرف انبار، استفاده از همان سریال قبلی
     */
    public function assignPermitSerial(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $normalizeSerial = function ($value) use ($persianDigits, $englishDigits) {
                return str_replace($persianDigits, $englishDigits, trim((string) $value));
            };

            $permit = DB::table('permit_requests')->where('id', $id)->lockForUpdate()->first();
            if (!$permit) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            if (!empty($permit->serial_number)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'برای این پرونده قبلاً سریال ثبت شده است.'], 422);
            }

            $destination = DB::table('permit_request_items')->where('permit_request_id', $id)->first();
            if (!$destination) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'مسیر سفر و کشور مقصد پرونده یافت نشد.'], 422);
            }

            $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
            $validityDaysInput = trim((string) $request->input('validity_days', ''));
            $validityDays = (!empty($validityDaysInput) && is_numeric($validityDaysInput))
                ? (int) $validityDaysInput
                : ($countryInfo->validity_days ?? 30);

            if ($validityDays <= 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'تعداد روز اعتبار معتبر نیست.'], 422);
            }

            if ($validityDays > 3650) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'تعداد روز اعتبار بیش از حد مجاز است.'], 422);
            }

            $issuedAt = now();
            $validUntil = $issuedAt->copy()->startOfDay()->addDays($validityDays);
            $isRenewal = (($permit->request_type ?? null) === 'renewal');
            $allocatedSerial = null;

            if ($isRenewal) {
                // در تمدید، سریال جدید از انبار مصرف نمی‌شود؛ همان شماره دوزوله قبلی تمدید می‌شود.
                $allocatedSerial = $normalizeSerial($permit->previous_serial_number ?? $permit->previous_d_code ?? '');

                if ($allocatedSerial === '') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'این پرونده تمدیدی است اما شماره سریال قبلی در پرونده ثبت نشده است.'
                    ], 422);
                }

                if (!empty($permit->previous_request_id)) {
                    $previousPermit = DB::table('permit_requests')
                        ->where('id', $permit->previous_request_id)
                        ->first();

                    if (!$previousPermit || ($previousPermit->status ?? null) !== 'issued' || empty($previousPermit->serial_number)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'پرونده قبلی برای تمدید معتبر نیست یا شماره دوزوله ندارد.'
                        ], 422);
                    }
                }
            } else {
                $manualSerial = $normalizeSerial($request->input('serial_number', ''));

                if ($manualSerial === '') {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'لطفاً شماره سریال دوزوله کشور را وارد کنید.'], 422);
                }

                if (!preg_match('/^[0-9]+$/', $manualSerial)) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'شماره سریال باید فقط عدد باشد.'], 422);
                }

                // درخواست جدید: بررسی موجودی انبار و مصرف برگه خام
                $warehouseItem = DB::table('dozbalagh_items')
                    ->whereIn('batch_id', function($q) use ($destination) {
                        $q->select('id')->from('dozbalagh_batches')->where('country_id', $destination->country_id);
                    })
                    ->where('serial_number', $manualSerial)
                    ->where(function($query) {
                        $query->whereNull('lifecycle_status')
                              ->orWhere('lifecycle_status', 'raw')
                              ->orWhereNotIn('lifecycle_status', ['consumed', 'issued', 'collected', 'archived', 'lost']);
                    })
                    ->select('id', 'serial_number')
                    ->lockForUpdate()
                    ->first();

                if (!$warehouseItem) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'این شماره سریال برای کشور مقصد این پرونده در انبار خام موجود نیست، قبلاً مصرف شده یا متعلق به کشور دیگری است.'
                    ], 422);
                }

                $allocatedSerial = $warehouseItem->serial_number;

                DB::table('dozbalagh_items')->where('id', $warehouseItem->id)->update([
                    'lifecycle_status' => 'consumed',
                    'updated_at' => $issuedAt
                ]);
            }

            $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
            if ($wallet) {
                DB::table('wallets')->where('id', $wallet->id)->update([
                    'blocked_balance' => max(0, $wallet->blocked_balance - $permit->total_amount),
                    'updated_at' => $issuedAt
                ]);
            }

            $permitUpdateData = [
                'serial_number'       => $allocatedSerial,
                'issued_at'           => $issuedAt,
                'validity_days'       => $validityDays,
                'permit_valid_until'  => $validUntil->toDateString(),
                'status'              => 'issued',
                'payment_status'      => 'settled',
                'updated_at'          => $issuedAt
            ];

            // اگر در دیتابیس ستون d_serial_number برای permit_requests وجود داشته باشد، همان سریال در آن هم ثبت می‌شود.
            if (Schema::hasColumn('permit_requests', 'd_serial_number')) {
                $permitUpdateData['d_serial_number'] = $allocatedSerial;
            }

            DB::table('permit_requests')->where('id', $id)->update($permitUpdateData);

            DB::table('permit_request_items')
                ->where('permit_request_id', $id)
                ->update([
                    'd_serial_number'   => $allocatedSerial,
                    'allocation_status' => 'allocated',
                    'updated_at'        => $issuedAt
                ]);

            DB::commit();

            $message = $isRenewal
                ? "تمدید دوزوله با همان شماره {$allocatedSerial} با موفقیت صادر شد."
                : "سریال {$allocatedSerial} با موفقیت ثبت و پرونده صادر شد.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'serial' => $allocatedSerial,
                'valid_until' => \Morilog\Jalali\Jalalian::fromCarbon($validUntil)->format('Y/m/d'),
                'remaining_days' => $validityDays,
                'request_type' => $isRenewal ? 'renewal' : 'new'
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Assign Permit Serial Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطای دیتابیس در صدور پروانه: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return view('association.driver.show', compact('id'));
    } 

    private function driverEventTypeLabels(): array
    {
        return [
            'started_trip' => 'شروع سفر',
            'in_transit' => 'در حال تردد',
            'at_border_out' => 'مرز خروجی',
            'reached_border' => 'رسیده به مرز',
            'at_destination' => 'گمرک مقصد',
            'delivered' => 'تحویل مقصد',
            'returned' => 'بازگشت لاشه',
            'problem' => 'اعلام مشکل',
            'delay' => 'اعلام تاخیر',
        ];
    }

    private function permitStatusLabels(): array
    {
        return [
            'pending' => 'در انتظار بررسی',
            'approved' => 'منتظر تخصیص سریال',
            'issued' => 'صادر شده / در تردد',
            'rejected' => 'رد شده',
            'collected' => 'تحویل شده',
            'archived' => 'بایگانی شده',
            'lost' => 'مفقودی',
            'returned' => 'برگشتی',
        ];
    }

    public function transitPermits()
    {
        try {
            $requests = DB::table('permit_requests')
                ->where('status', 'issued')
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_id', $req->fleet_id)->first()
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }

                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                $req->country_name = 'نامشخص';
                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                    }
                }
            }

            return view('association.driver.transit', compact('requests'));
        } catch (\Throwable $e) {
            Log::error('Transit Permits Load Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری کارتابل تردد: ' . $e->getMessage());
        }
    }

    public function settleTransitPermit(Request $request, $id)
    {
        return $this->updateRequestStatus($request, $id);
    }

    public function archivePermits()
    {
        try {
            $requests = DB::table('permit_requests')
                ->whereIn('status', ['archived', 'collected', 'lost'])
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }

                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                $req->country_name = 'نامشخص';
                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                    }
                }
            }

            foreach ($requests as $req) {
                $items = DB::table('permit_request_items')
                    ->where('permit_request_id', $req->id)
                    ->get();

                $req->dbDetails = $items->first();
                $req->destinations = $items->map(function ($item) {
                    $country = DB::table('countries')->where('id', $item->country_id)->first();

                    return [
                        'country_name' => $country ? $country->name : 'نامشخص',
                        'permit_type' => $item->permit_type ?? '---',
                        'operation_type' => $item->operation_type ?? '---',
                        'loading_origin' => $item->loading_origin ?? '---',
                        'loading_destination' => $item->loading_destination ?? '---',
                        'cits_code' => $item->cits_code ?? '---',
                        'trip_code' => $item->trip_code ?? '---',
                        'receipt_code' => $item->receipt_code ?? '---',
                        'receipt_amount' => $item->receipt_amount ?? 0,
                        'cmr_date' => $item->cmr_date ?? '---',
                        'tir_carnet_number' => $item->tir_carnet_number ?? '---',
                        'tir_carnet_date' => $item->tir_carnet_date ?? '---',
                    ];
                })->values();
            }

            return view('association.driver.archive', compact('requests'));
        } catch (\Throwable $e) {
            Log::error('Archive Permits Load Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری بایگانی کل: ' . $e->getMessage());
        }
    }

    public function printPermit($id)
    {
        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return abort(404, 'پرونده یافت نشد.');
            }

            $driver = DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->first();
            $fleet = DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->first();

            $destination = DB::table('permit_request_items')->where('permit_request_id', $id)->first();
            $country = $destination ? DB::table('countries')->where('id', $destination->country_id)->first() : null;
            $countryId = $country ? $country->id : 'default';

            if (view()->exists("association.driver.prints.{$countryId}")) {
                return view("association.driver.prints.{$countryId}", compact('permit', 'driver', 'fleet', 'country'));
            }

            return view('association.driver.print_document', compact('permit', 'driver', 'fleet', 'country'));
            
        } catch (\Throwable $e) {
            Log::error('Print Permit Error: ' . $e->getMessage());
            return abort(500, 'خطا در لود صفحه چاپ: ' . $e->getMessage());
        }
    }

    /**
     * 🛑 غیرفعال‌سازی قطعی سناریوی برگشت برای اصلاح به دستور مدیریت
     */
    public function returnToCompany(Request $request, $id)
    {
        return response()->json([
            'success' => false, 
            'message' => 'این عملیات به دستور مدیریت از سیستم حذف شده است. لطفاً از گزینه رد کامل استفاده کنید.'
        ], 422);
    }
}

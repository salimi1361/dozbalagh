<?php

namespace App\Http\Controllers\Association;

use App\Models\Driver;
use App\Notifications\DriverPermitIssuedNotification;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Throwable;
use App\Services\PermitPrintService;

class AssociationController
{
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
            $requests = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
                ->where('pr.status', 'approved')
                ->whereNull('pri.d_serial_number')
                ->select([
                    'pr.*',
                    'pri.id as item_id',
                    'pri.country_id as item_country_id',
                    'pri.permit_type as item_permit_type',
                    'pri.price as item_price',
                    'pri.d_serial_number as item_serial_number',
                    'pri.item_status',
                    'pri.permit_valid_until as item_valid_until',
                ])
                ->orderByDesc('pr.id')
                ->orderBy('pri.id')
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

                if ($req->item_country_id) {
                    $countryInfo = DB::table('countries')->where('id', $req->item_country_id)->first();
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
                            ->where('dozbalagh_batches.country_id', $req->item_country_id)
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

            $destination = DB::table('permit_request_items')->where('id', $id)->lockForUpdate()->first();
            if (!$destination) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'آیتم کشور/مسیر این دوزوله یافت نشد.'], 404);
            }

            if (!empty($destination->d_serial_number)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'برای این کشور قبلاً سریال ثبت شده است.'], 422);
            }

            $permit = DB::table('permit_requests')->where('id', $destination->permit_request_id)->lockForUpdate()->first();
            if (!$permit) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            if (($permit->status ?? null) !== 'approved') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'این پرونده در وضعیت آماده صدور نیست.'], 422);
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
                $allocatedSerial = $normalizeSerial($permit->previous_serial_number ?? $destination->d_serial_number ?? $permit->previous_d_code ?? '');

                if ($allocatedSerial === '') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'این پرونده تمدیدی است اما شماره سریال قبلی در پرونده ثبت نشده است.'
                    ], 422);
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

            DB::table('permit_request_items')
                ->where('id', $destination->id)
                ->update([
                    'd_serial_number'     => $allocatedSerial,
                    'allocation_status'   => 'allocated',
                    'item_status'         => 'issued',
                    'issued_at'           => $issuedAt,
                    'validity_days'       => $validityDays,
                    'permit_valid_until'  => $validUntil->toDateString(),
                    'updated_at'          => $issuedAt,
                ]);

            $remainingPendingItems = DB::table('permit_request_items')
                ->where('permit_request_id', $permit->id)
                ->whereNull('d_serial_number')
                ->count();

            if ($remainingPendingItems === 0) {
                $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
                if ($wallet) {
                    DB::table('wallets')->where('id', $wallet->id)->update([
                        'blocked_balance' => max(0, $wallet->blocked_balance - $permit->total_amount),
                        'updated_at' => $issuedAt
                    ]);
                }

                $firstSerial = DB::table('permit_request_items')
                    ->where('permit_request_id', $permit->id)
                    ->orderBy('id')
                    ->value('d_serial_number');

                $permitUpdateData = [
                    'serial_number'       => $firstSerial ?: $allocatedSerial,
                    'issued_at'           => $issuedAt,
                    'validity_days'       => $validityDays,
                    'permit_valid_until'  => $validUntil->toDateString(),
                    'status'              => 'issued',
                    'payment_status'      => 'settled',
                    'updated_at'          => $issuedAt
                ];

                // اگر در دیتابیس ستون d_serial_number برای permit_requests وجود داشته باشد، اولین سریال جهت سازگاری ثبت می‌شود.
                if (Schema::hasColumn('permit_requests', 'd_serial_number')) {
                    $permitUpdateData['d_serial_number'] = $firstSerial ?: $allocatedSerial;
                }

                DB::table('permit_requests')->where('id', $permit->id)->update($permitUpdateData);
            } else {
                DB::table('permit_requests')->where('id', $permit->id)->update([
                    'updated_at' => $issuedAt,
                ]);
            }

            DB::commit();

            $this->notifyDriverPermitIssued($permit->id, (string) $allocatedSerial, $permit->driver_id, $permit->company_id, $validUntil->toDateString());

            $countryName = $countryInfo->name ?? 'نامشخص';
            $message = $isRenewal
                ? "تمدید دوزوله کشور {$countryName} با شماره {$allocatedSerial} صادر شد."
                : "سریال {$allocatedSerial} برای کشور {$countryName} ثبت شد.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'serial' => $allocatedSerial,
                'item_id' => $destination->id,
                'permit_id' => $permit->id,
                'all_items_issued' => $remainingPendingItems === 0,
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

    private function notifyDriverPermitIssued(int $permitId, string $serialNumber, mixed $driverId, mixed $companyId, ?string $validUntil): void
    {
        try {
            if (blank($driverId)) {
                return;
            }

            $driver = Driver::where(function ($query) use ($driverId) {
                    $query->where('id', $driverId)
                        ->orWhere('national_code', $driverId);
                })
                ->first();

            if (!$driver) {
                return;
            }

            $company = DB::table('companies')->where('id', $companyId)->first();
            $companyName = $company->name_fa ?? $company->name ?? 'شرکت حمل و نقل';

            $driver->notify(new DriverPermitIssuedNotification(
                $permitId,
                $serialNumber,
                $companyName,
                $validUntil
            ));

            $driverAppUrl = url('/driver/index.html');
            $sms = "سامانه دوزوله\nراننده گرامی، دوزوله شماره {$serialNumber} توسط {$companyName} برای شما صادر شد.\nبرای ورود به وب‌اپ راننده ابتدا برنامه را از لینک زیر نصب کنید و سپس از آیکن نصب‌شده وارد سامانه شوید:\n{$driverAppUrl}";
            app(SmsService::class)->send($driver->mobile, $sms, 'driver_permit_issued');
        } catch (\Throwable $e) {
            Log::warning('Driver permit issued notification failed: ' . $e->getMessage());
        }
    }

    public function transitPermits()
    {
        try {
            $hasItemStatus = Schema::hasColumn('permit_request_items', 'item_status');
            $hasCompanyReturnImage = Schema::hasColumn('permit_request_items', 'company_return_image');
            $hasCourierName = Schema::hasColumn('permit_request_items', 'courier_name');
            $hasCourierMobile = Schema::hasColumn('permit_request_items', 'courier_mobile');
            $hasCourierDeliveryCode = Schema::hasColumn('permit_request_items', 'courier_delivery_code');

            $selects = [
                'pr.*',
                'pri.id as item_id',
                'pri.country_id as item_country_id',
                'pri.permit_type as item_permit_type',
                'pri.d_serial_number as item_serial_number',
                'c.name as item_country_name',
            ];

            $selects[] = $hasItemStatus
                ? 'pri.item_status'
                : DB::raw("'issued' as item_status");
            $selects[] = $hasCompanyReturnImage
                ? 'pri.company_return_image as item_company_return_image'
                : DB::raw('pri.return_cmr_file as item_company_return_image');
            $selects[] = $hasCourierName
                ? 'pri.courier_name as item_courier_name'
                : DB::raw('NULL as item_courier_name');
            $selects[] = $hasCourierMobile
                ? 'pri.courier_mobile as item_courier_mobile'
                : DB::raw('NULL as item_courier_mobile');
            $selects[] = $hasCourierDeliveryCode
                ? 'pri.courier_delivery_code as item_courier_delivery_code'
                : DB::raw('NULL as item_courier_delivery_code');
            $selects[] = DB::raw('pri.rejection_reason as item_return_meta');

            $query = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
                ->leftJoin('countries as c', 'c.id', '=', 'pri.country_id')
                ->where('pr.status', 'issued')
                ->whereNotNull('pri.d_serial_number')
                ->orderBy('pri.updated_at', 'desc')
                ->select($selects);

            if ($hasItemStatus) {
                $query->where(function ($query) {
                    $query->whereNull('pri.item_status')
                        ->orWhereNotIn('pri.item_status', ['lost', 'collected', 'archived']);
                });
            } else {
                $query->where(function ($query) {
                    $query->whereNull('pri.return_status')
                        ->orWhereNotIn('pri.return_status', ['lost', 'collected', 'archived']);
                });
            }

            $requests = $query->paginate(10);

            foreach ($requests as $req) {
                $req->serial_number = $req->item_serial_number ?: $req->serial_number;
                $req->company_return_image = $req->item_company_return_image;
                $req->courier_name = $req->item_courier_name;
                $req->courier_mobile = $req->item_courier_mobile;
                $req->courier_delivery_code = $req->item_courier_delivery_code;
                if ((!$req->courier_name || !$req->courier_mobile || !$req->courier_delivery_code) && !empty($req->item_return_meta)) {
                    $meta = json_decode($req->item_return_meta, true);
                    if (is_array($meta)) {
                        $req->courier_name = $req->courier_name ?: ($meta['cn'] ?? null);
                        $req->courier_mobile = $req->courier_mobile ?: ($meta['cm'] ?? null);
                        $req->courier_delivery_code = $req->courier_delivery_code ?: ($meta['dc'] ?? null);
                    }
                }

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

                $req->country_name = $req->item_country_name ?: 'نامشخص';
            }

            return view('association.driver.transit', compact('requests'));
        } catch (\Throwable $e) {
            Log::error('Transit Permits Load Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری کارتابل تردد: ' . $e->getMessage());
        }
    }

    public function settleTransitPermit(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'courier_code' => 'nullable|string',
            'image' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        try {
            $status = $request->input('status');
            if (!in_array($status, ['collected', 'archived', 'lost'], true)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'وضعیت انتخاب شده برای تحویل لاشه معتبر نیست.',
                ], 422);
            }

            $now = Carbon::now();
            $userId = auth()->id() ?? null;
            $hasItemStatus = Schema::hasColumn('permit_request_items', 'item_status');
            $hasCompanyReturnFields = Schema::hasColumn('permit_request_items', 'company_return_image')
                && Schema::hasColumn('permit_request_items', 'courier_delivery_code')
                && Schema::hasColumn('permit_request_items', 'courier_received_at')
                && Schema::hasColumn('permit_request_items', 'courier_received_by_user_id')
                && Schema::hasColumn('permit_request_items', 'collected_image')
                && Schema::hasColumn('permit_request_items', 'closed_at');
            $item = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
                ->where('pri.id', $id)
                ->where('pr.status', 'issued')
                ->whereNotNull('pri.d_serial_number')
                ->select('pri.*', 'pr.driver_id', 'pr.fleet_id', 'pr.d_code')
                ->lockForUpdate()
                ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'مجوز کشور مورد نظر پیدا نشد یا قبلا تعیین تکلیف شده است.',
                ], 404);
            }

            $itemStatus = $hasItemStatus ? ($item->item_status ?? 'issued') : ($item->return_status ?? 'issued');
            if (in_array($itemStatus, ['collected', 'archived', 'lost'], true)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'این مجوز قبلا تعیین تکلیف شده است.',
                ], 422);
            }

            $returnImage = $hasCompanyReturnFields
                ? ($item->company_return_image ?? null)
                : ($item->return_cmr_file ?? null);
            $courierCode = $hasCompanyReturnFields
                ? ($item->courier_delivery_code ?? null)
                : null;
            if (!$courierCode && !empty($item->rejection_reason)) {
                $meta = json_decode($item->rejection_reason, true);
                if (is_array($meta)) {
                    $courierCode = $meta['dc'] ?? null;
                }
            }

            $updateData = $hasItemStatus
                ? ['item_status' => $status, 'updated_at' => $now]
                : ['return_status' => $status, 'updated_at' => $now];

            if ($status === 'collected' || $status === 'archived') {
                if (empty($returnImage) || empty($courierCode)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'شرکت هنوز لاشه و مشخصات پیک را برای این کشور ثبت نکرده است.',
                    ], 422);
                }

                if (trim((string) $request->input('courier_code')) !== trim((string) $courierCode)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'کد تحویل پیک صحیح نیست.',
                    ], 422);
                }

                if ($hasCompanyReturnFields) {
                    $updateData['courier_received_at'] = $now;
                    $updateData['courier_received_by_user_id'] = $userId;
                    $updateData['collected_image'] = $returnImage;
                    $updateData['closed_at'] = $now;
                }
            }

            if ($status === 'lost' && $hasCompanyReturnFields) {
                $updateData['lost_reported_at'] = $now;
                $updateData['closed_at'] = $now;
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $serialClean = $item->d_serial_number ?? 'serial';
                $fileName = $serialClean . '-' . $item->d_code . '.' . $file->getClientOriginalExtension();
                $file->storeAs('permits/collected', $fileName, 'public');
                if ($hasCompanyReturnFields) {
                    $updateData['collected_image'] = 'permits/collected/' . $fileName;
                } else {
                    $updateData['return_cmr_file'] = 'permits/collected/' . $fileName;
                }
            }

            DB::table('permit_request_items')->where('id', $item->id)->update($updateData);

            DB::table('dozbalagh_items')
                ->where('serial_number', $item->d_serial_number)
                ->update([
                    'lifecycle_status' => $status,
                    'returned_at' => $now,
                    'updated_at' => $now,
                ]);

            $activeItems = DB::table('permit_request_items')
                ->where('permit_request_id', $item->permit_request_id)
                ->whereNotNull('d_serial_number')
                ->when($hasItemStatus, function ($query) {
                    $query->where(function ($query) {
                        $query->whereNull('item_status')
                            ->orWhereNotIn('item_status', ['lost', 'collected', 'archived']);
                    });
                }, function ($query) {
                    $query->where(function ($query) {
                        $query->whereNull('return_status')
                            ->orWhereNotIn('return_status', ['lost', 'collected', 'archived']);
                    });
                })
                ->count();

            if ($activeItems === 0) {
                $terminalStatuses = DB::table('permit_request_items')
                    ->where('permit_request_id', $item->permit_request_id)
                    ->whereNotNull('d_serial_number')
                    ->pluck($hasItemStatus ? 'item_status' : 'return_status')
                    ->filter()
                    ->values();

                $parentStatus = $terminalStatuses->every(fn ($value) => $value === 'lost') ? 'lost' : 'archived';
                $parentUpdate = [
                    'status' => $parentStatus,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('permit_requests', 'closed_at')) {
                    $parentUpdate['closed_at'] = $now;
                }

                DB::table('permit_requests')->where('id', $item->permit_request_id)->update($parentUpdate);

                if ($item->driver_id && Schema::hasColumn('drivers', 'is_blocked')) {
                    DB::table('drivers')->where('id', $item->driver_id)->orWhere('national_code', $item->driver_id)->update(['is_blocked' => false]);
                }
                if ($item->fleet_id && Schema::hasColumn('fleets', 'is_blocked')) {
                    DB::table('fleets')->where('id', $item->fleet_id)->orWhere('smart_card_number', $item->fleet_id)->update(['is_blocked' => false]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'لاشه همین کشور با موفقیت تحویل و ثبت شد.',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Settle Transit Permit Item Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت تحویل لاشه: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function archivePermits(Request $request)
    {
        try {
            $hasItemStatus = Schema::hasColumn('permit_request_items', 'item_status');
            $hasCompanyReturnImage = Schema::hasColumn('permit_request_items', 'company_return_image');
            $hasCourierName = Schema::hasColumn('permit_request_items', 'courier_name');
            $hasCourierMobile = Schema::hasColumn('permit_request_items', 'courier_mobile');
            $hasCourierDeliveryCode = Schema::hasColumn('permit_request_items', 'courier_delivery_code');
            $hasCourierReceivedAt = Schema::hasColumn('permit_request_items', 'courier_received_at');
            $hasCollectedImage = Schema::hasColumn('permit_request_items', 'collected_image');
            $hasClosedAt = Schema::hasColumn('permit_request_items', 'closed_at');
            $hasParentClosedAt = Schema::hasColumn('permit_requests', 'closed_at');
            $archiveCountryFilter = $request->query('country_id', 'all');

            $applyArchiveStatusFilter = function ($query) use ($hasItemStatus) {
                $query->where(function ($query) use ($hasItemStatus) {
                    if ($hasItemStatus) {
                        $query->whereIn('pri.item_status', ['archived', 'collected', 'lost'])
                            ->orWhereIn('pri.return_status', ['archived', 'collected', 'lost'])
                            ->orWhereIn('pr.status', ['archived', 'collected', 'lost']);
                    } else {
                        $query->whereIn('pri.return_status', ['archived', 'collected', 'lost'])
                            ->orWhereIn('pr.status', ['archived', 'collected', 'lost']);
                    }
                });
            };

            $archiveCountriesQuery = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
                ->leftJoin('countries as c', 'c.id', '=', 'pri.country_id')
                ->whereNotNull('pri.d_serial_number');

            $applyArchiveStatusFilter($archiveCountriesQuery);

            $archiveCountries = $archiveCountriesQuery
                ->selectRaw("COALESCE(pri.country_id, 0) as country_id, COALESCE(c.name, 'نامشخص') as country_name, COUNT(*) as total")
                ->groupByRaw("COALESCE(pri.country_id, 0), COALESCE(c.name, 'نامشخص')")
                ->orderBy('country_name')
                ->get();

            $archiveTotalCount = (int) $archiveCountries->sum('total');

            $selects = [
                'pr.*',
                'pri.id as item_id',
                'pri.country_id as item_country_id',
                'pri.permit_type as item_permit_type',
                'pri.operation_type as item_operation_type',
                'pri.d_serial_number as item_serial_number',
                'pri.return_status',
                'pri.return_cmr_file',
                'pri.rejection_reason as item_return_meta',
                'c.name as item_country_name',
            ];

            $selects[] = $hasItemStatus
                ? 'pri.item_status'
                : DB::raw('pri.return_status as item_status');
            $selects[] = $hasCompanyReturnImage
                ? 'pri.company_return_image as item_company_return_image'
                : DB::raw('pri.return_cmr_file as item_company_return_image');
            $selects[] = $hasCourierName
                ? 'pri.courier_name as item_courier_name'
                : DB::raw('NULL as item_courier_name');
            $selects[] = $hasCourierMobile
                ? 'pri.courier_mobile as item_courier_mobile'
                : DB::raw('NULL as item_courier_mobile');
            $selects[] = $hasCourierDeliveryCode
                ? 'pri.courier_delivery_code as item_courier_delivery_code'
                : DB::raw('NULL as item_courier_delivery_code');
            $selects[] = $hasCourierReceivedAt
                ? 'pri.courier_received_at as item_courier_received_at'
                : DB::raw('NULL as item_courier_received_at');
            $selects[] = $hasCollectedImage
                ? 'pri.collected_image as item_collected_image'
                : DB::raw('pri.return_cmr_file as item_collected_image');
            $selects[] = $hasClosedAt
                ? 'pri.closed_at as item_closed_at'
                : ($hasParentClosedAt ? DB::raw('pr.closed_at as item_closed_at') : DB::raw('NULL as item_closed_at'));

            $query = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
                ->leftJoin('countries as c', 'c.id', '=', 'pri.country_id')
                ->whereNotNull('pri.d_serial_number')
                ->orderBy('pri.updated_at', 'desc')
                ->select($selects);

            $applyArchiveStatusFilter($query);

            if ($archiveCountryFilter !== 'all') {
                if ((string) $archiveCountryFilter === '0') {
                    $query->whereNull('pri.country_id');
                } elseif (is_numeric($archiveCountryFilter)) {
                    $query->where('pri.country_id', (int) $archiveCountryFilter);
                }
            }

            $requests = $query->paginate(10)->withQueryString();

            foreach ($requests as $req) {
                $itemStatus = in_array($req->item_status, ['archived', 'collected', 'lost'], true)
                    ? $req->item_status
                    : (in_array($req->return_status, ['archived', 'collected', 'lost'], true) ? $req->return_status : $req->status);
                $req->status = in_array($itemStatus, ['archived', 'collected', 'lost'], true) ? $itemStatus : $req->status;
                $req->serial_number = $req->item_serial_number ?: $req->serial_number;
                $req->country_name = $req->item_country_name ?: 'نامشخص';
                $req->company_return_image = $req->item_company_return_image;
                $req->collected_image = $req->item_collected_image ?: ($req->item_company_return_image ?: $req->return_cmr_file);
                $req->courier_name = $req->item_courier_name;
                $req->courier_mobile = $req->item_courier_mobile;
                $req->courier_delivery_code = $req->item_courier_delivery_code;
                $req->courier_received_at = $req->item_courier_received_at;
                $req->closed_at = $req->item_closed_at ?: ($req->closed_at ?? null);

                if ((!$req->courier_name || !$req->courier_mobile || !$req->courier_delivery_code) && !empty($req->item_return_meta)) {
                    $meta = json_decode($req->item_return_meta, true);
                    if (is_array($meta)) {
                        $req->courier_name = $req->courier_name ?: ($meta['cn'] ?? null);
                        $req->courier_mobile = $req->courier_mobile ?: ($meta['cm'] ?? null);
                        $req->courier_delivery_code = $req->courier_delivery_code ?: ($meta['dc'] ?? null);
                    }
                }

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
            }

            foreach ($requests as $req) {
                $items = DB::table('permit_request_items')
                    ->where('permit_request_id', $req->id)
                    ->get();

                $req->dbDetails = $items->firstWhere('id', $req->item_id) ?: $items->first();
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

            return view('association.driver.archive', compact(
                'requests',
                'archiveCountries',
                'archiveCountryFilter',
                'archiveTotalCount'
            ));
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

    public function printPermitItem(Request $request, $id, PermitPrintService $printService)
    {
        try {
            $printContext = $printService->contextForItem((int)$id);
            if ($printContext['layout']) {
                $mode = $request->query('mode', 'original');
                if (!in_array($mode, ['original', 'copy'], true)) $mode = 'original';
                return view('association.driver.dynamic_print', $printContext + compact('mode'));
            }

            $item = DB::table('permit_request_items')->where('id', $id)->first();
            if (!$item) {
                return abort(404, 'آیتم دوزوله یافت نشد.');
            }

            $permit = DB::table('permit_requests')->where('id', $item->permit_request_id)->first();
            if (!$permit) {
                return abort(404, 'پرونده یافت نشد.');
            }

            $driver = DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->first();
            $fleet = DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->first();
            $country = DB::table('countries')->where('id', $item->country_id)->first();
            $countryId = $country ? $country->id : 'default';

            $permit->serial_number = $item->d_serial_number ?: $permit->serial_number;
            $permit->issued_at = $item->issued_at ?: $permit->issued_at;
            $permit->validity_days = $item->validity_days ?: $permit->validity_days;
            $permit->permit_valid_until = $item->permit_valid_until ?: $permit->permit_valid_until;
            $permit->print_item_id = $item->id;
            $permit->print_permit_type = $item->permit_type;

            if (view()->exists("association.driver.prints.{$countryId}")) {
                return view("association.driver.prints.{$countryId}", compact('permit', 'driver', 'fleet', 'country', 'item'));
            }

            return view('association.driver.print_document', compact('permit', 'driver', 'fleet', 'country', 'item'));
        } catch (\Throwable $e) {
            Log::error('Print Permit Item Error: ' . $e->getMessage());
            return abort(500, 'خطا در لود صفحه چاپ آیتم: ' . $e->getMessage());
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
